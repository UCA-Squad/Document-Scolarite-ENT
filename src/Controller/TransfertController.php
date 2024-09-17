<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Entity\Student;
use App\Logic\CustomFinder;
use App\Logic\DocapostFast;
use App\Logic\FileAccess;
use App\Logic\LDAP;
use App\Repository\ImportedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[Route('/api/transfert'), IsGranted('ROLE_SCOLA')]
class TransfertController extends AbstractController
{
    public function __construct(private FileAccess   $file_access, private CustomFinder $finder, private ParameterBagInterface $params,
                                private DocapostFast $docapost, private EntityManagerInterface $em, private ImportedDataRepository $repo)
    {
    }

    #[Route('/mail/template', name: 'api_mail_template')]
    public function api_get_mail_template(Request $request): Response
    {
        $mode = 0;

        $stud = $request->getSession()->get('students')[0];
        $bddData = $request->getSession()->get('data');

//        $this->session->clear();

        $this->finder->deleteDirectory($this->file_access->getTamponFolder());
        $this->finder->deleteDirectory($this->file_access->getTmpByMode($mode));
        $this->finder->deleteDirectory($this->file_access->getPdfByMode($mode));
        $this->finder->deleteDirectory($this->file_access->getEtuByMode($mode));

        return $this->render('Mail/add_doc_mail.html.twig', [
            'stud' => $stud,
            'mode' => $mode,
            'bddData' => $bddData
        ]);
    }

    #[Route('/releves', name: 'transfert_rn', methods: ['POST'])]
    public function transfert_rn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Post params: int[]
        $nums = $data['nums'];
        $mode = $data['mode'];

        // How many files to transfert at once
        $batchCount = 100;

        $data = $request->getSession()->get('data');


//        try {

        for ($i = 0; $i < $batchCount && $i < count($nums); $i++) {
            $this->transfert($mode === ImportedData::RN ? ImportedData::RN : ImportedData::ATTEST, $nums[$i], $data);
        }

        $request->getSession()->set('data', $data);


        $numsTodo = array_slice($nums, $i);
        unset($nums);

        // Last batch
        if (empty($numsTodo)) {
            $import = $request->getSession()->get('data');

            if ($import->getId() !== null) {
                $existingImport = $this->repo->find($import->getId());
                $existingImport->addHistory($import->getLastHistory());
            } else {
                $this->em->persist($import);
            }
            $this->em->flush();
        }
        return new JsonResponse($numsTodo);
//        } catch (\Exception $e) {
//            // On error, save the already processed data
//            $import = $this->session->get('data');
//            $this->em->persist($import);
//            $this->em->flush();
//            return new JsonResponse($e->getMessage(), 500);
//        }
    }

    private function transfert(int $mode, int $num, ImportedData $data): void
    {
        $from = $this->file_access->getTmpByMode($mode);
        $to = $this->file_access->getDirByMode($mode);

        if (!is_dir($to)) mkdir($to);
        if (!is_dir($to . $num)) mkdir($to . $num);

        $fileFrom = $this->finder->getFirstFile($from . $num);
        $index = $this->finder->getFileIndex($to . $num, $fileFrom);

        $newDoc = true;

        if ($index != -1) {
            // Document existant
            unlink($to . $num . '/' . $this->finder->getFileByIndex($to . $num, $index));
            $newDoc = false;
        }

        if ($this->docapost->isEnable()) {
            // Génére nom random
            $randName = $this->docapost->getSiren() . bin2hex(random_bytes(5)) . '.pdf';
            // Met à jour le nom du fichier
            rename($from . $num . '/' . $fileFrom, $from . $num . '/' . $randName);

            try {
                // Envoi sur docapost
                $id = $this->docapost->uploadDocument($from . $num . '/' . $randName, 'test');

                $isSigned = $this->docapost->isSigned($id);
                if (!$isSigned) {
                    rename($from . $num . '/' . $randName, $from . $num . '/' . $fileFrom);
                    throw new \Exception("Document non signé par le serveur docapost");
                }

                // Récupère le binaire pdf signé
                $docaDoc = $this->docapost->downloadDocument($id);
                // Écris le pdf reçu dans le dossier de destination
                file_put_contents($to . $num . '/' . $fileFrom, $docaDoc);
                // Supprime le fichier temporaire
                unlink($from . $num . '/' . $randName);
            } catch (\Exception $e) {
                rename($from . $num . '/' . $randName, $from . $num . '/' . $fileFrom);
                throw $e;
            }
        } else {
            rename($from . $num . '/' . $fileFrom, $to . $num . '/' . $fileFrom);
        }

        $hist = $data->getHistory()->last();

        if ($data->getHistory()->count() > 1)
            $hist->setState(History::Transfered);

        if ($newDoc)
            $hist->setNbFiles($hist->getNbFiles() + 1);

        $hist->setDate();

        // Supprime les dossiers temporaires vides
        if (is_dir($from . $num) && empty($this->finder->getFilesName($from . $num)))
            $this->finder->deleteDirectory($from . $num);
    }

    #[Route('/mail', name: 'send_mails')]
    public function send_mails(Request                $request, MailerInterface $mailer, Environment $twig,
                               ImportedDataRepository $repo, LDAP $ldap): JsonResponse
    {
        $params = json_decode($request->getContent(), true);
        $ids = $params['numsEtu'];
        $mode = $params['mode'];

        $etu = $mode == ImportedData::RN ? $this->getParameter("output_etu_rn") : $this->getParameter("output_etu_attest");

        $bddData = $repo->findOneBy(['username' => $this->getUser()->getUserIdentifier()], ['id' => 'DESC']); // findLastDataByMode($mode, $this->getUser()->getUsername());

        $students = $request->getSession()->get('students');

        foreach ($students as $stud) {

            if (!isset($ids) || !in_array($stud->getNumero(), $ids))
                continue;

            $num = $stud->getNumero();
            $user = current($ldap->search("(CLFDcodeEtu=$num)", "ou=people,", ["CLFDcodeEtu", "CLFDstatus", "memberOf"]));

            // Vérifie que l'étudiant est actif et non blacklisté pour envoyer mail
            if (!isset($user) || $user->getAttribute("CLFDstatus")[0] == 0 /*||
                in_array($this->params->get("ldap")["bl_group"], $user->getAttribute("memberOf"))*/)
                continue;

            $this->send_mail($stud, $mode, $bddData, $twig, $mailer);
        }
        $request->getSession()->clear();
        $this->finder->deleteDirectory($etu . $this->getUser()->getUsername() . '.etu');

        return new JsonResponse();
    }

    private function send_mail(Student $stud, int $mode, ImportedData $bddData, Environment $twig, MailerInterface $mailer): void
    {
        if (empty($stud->getMail()))
            return;

        $body = $twig->render('Mail/add_doc_mail.html.twig', [
            'stud' => $stud,
            'mode' => $mode,
            'bddData' => $bddData
        ]);

        $to = $this->params->get('kernel.environment') == "dev" ? $this->getUser()->getEmail() : $stud->getMail();

        $msg = (new Email())
            ->from($this->getParameter('mail_sender'))
            ->to($to)
            ->subject($this->getParameter('mail_subject'))
            ->html($body);

        $mailer->send($msg);
    }
}