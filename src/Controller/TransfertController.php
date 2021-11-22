<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Entity\Student;
use App\Logic\CustomFinder;
use App\Logic\DocapostFast;
use App\Logic\FileAccess;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/transfert")
 * @IsGranted("ROLE_SCOLA")
 */
class TransfertController extends AbstractController
{
	private $file_access;
	private $finder;
	private $params;
	private $docapost;
	private $session;

	public function __construct(FileAccess $file_access, CustomFinder $finder, ParameterBagInterface $params, DocapostFast $docapost, SessionInterface $session)
	{
		$this->file_access = $file_access;
		$this->finder = $finder;
		$this->params = $params;
		$this->docapost = $docapost;
		$this->session = $session;
	}

	/**
	 * @Route("/releves", name="transfert_rn")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function transfert_rn(Request $request): JsonResponse
	{
		$ids = $request->get("ids");
		$num = $request->get('num');

		try {
			$result = $this->transfert(ImportedData::RN, $num, $ids);
			return new JsonResponse($result);
		} catch (\Exception $e) {
			return new JsonResponse($e->getMessage());
		}
	}

	/**
	 * @Route("/attests", name="transfert_attest")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function transfert_attest(Request $request): JsonResponse
	{
		$ids = $request->get("ids");
		$num = $request->get('num');

		try {
			$result = $this->transfert(ImportedData::ATTEST, $num, $ids);
			return new JsonResponse($result);
		} catch (\Exception $e) {
			return new JsonResponse($e->getMessage());
		}
	}

	private function transfert(int $mode, int $num, array $ids = null): bool
	{
		$from = $this->file_access->getTmpByMode($mode);
		$to = $this->file_access->getDirByMode($mode);

		if (!is_dir($to))
			mkdir($to);

		if (isset($ids) && in_array($num, $ids)) {
			return false;
		}

		if (!is_dir($to . $num))
			mkdir($to . $num);

		$fileFrom = $this->finder->getFirstFile($from . $num);
		$index = $this->finder->getFileIndex($to . $num, $fileFrom);
		if ($index != -1) {
			unlink($to . $num . '/' . $this->finder->getFileByIndex($to . $num, $index));
		}

		if ($this->docapost->isEnable()) {
			// Génére nom random
			$randName = bin2hex(random_bytes(5)) . '.pdf';
			// Met à jour le nom du fichier
			rename($from . $num . '/' . $fileFrom, $from . $num . '/' . $randName);
			// Envoi sur docapost
			$id = $this->docapost->uploadDocument($from . $num . '/' . $randName, 'test');
			// Récupère le binaire pdf signé
			$docaDoc = $this->docapost->downloadDocument($id);
			// Écris le pdf reçu dans le dossier de destination
			file_put_contents($to . $num . '/' . $fileFrom, $docaDoc);
			// Supprime le fichier temporaire
			unlink($from . $num . '/' . $randName);
		} else {
			rename($from . $num . '/' . $fileFrom, $to . $num . '/' . $fileFrom);
		}

		// Supprime les dossiers temporaires vides
		if (is_dir($from . $num) && empty($this->finder->getFilesName($from . $num)))
			$this->finder->deleteDirectory($from . $num);

		$this->addTransfertToSession($to . $num . '/' . $fileFrom);
		$this->update_transfered_files($mode, $from, $ids ?? []);
		return true;
	}

	/**
	 * Ajoute pathname à la liste (cache) des transferts effectués.
	 * @param string $pathname
	 */
	private function addTransfertToSession(string $pathname)
	{
		$transfered = $this->session->get('transfered');
		if (!isset($transfered))
			$transfered = [];
		$transfered[] = $pathname;
		$this->session->set('transfered', $transfered);
	}

	/**
	 * Update the field 'nbFiles' of the last ImportedData.
	 * @param int $mode
	 * @param array $ids
	 * @param string $from
	 */
	private function update_transfered_files(int $mode, string $from, array $ids = [])
	{
		$data = $this->getDoctrine()->getRepository(ImportedData::class)->findLastDataByMode($mode, $this->getUser()->getUsername());

		$data->getLastHistory()->setNbFiles($data->getLastHistory()->getNbFiles() + 1);
		$data->getLastHistory()->setState(History::Transfered);
		$data->getLastHistory()->setDate();

		$em = $this->getDoctrine()->getManager();
		$em->persist($data);
		$em->flush();

		// Si on a transféré tous les documents séléctionnés
//		if ($data->getLastHistory()->getNbFiles() == $data->getNbStudents() - count($ids)) {
//			$this->finder->deleteDirectory($from);
//		}
	}

	/**
	 * @Route("/mail", name="send_mails")
	 * @param Request $request
	 * @param MailerInterface $mailer
	 * @param Environment $twig
	 * @param ImportedDataRepository $repo
	 * @return JsonResponse
	 */
	public function send_mails(Request $request, MailerInterface $mailer, Environment $twig, ImportedDataRepository $repo): JsonResponse
	{
		$ids = $request->get('ids');
		$mode = $request->get('mode');

		$etu = $mode == ImportedData::RN ? $this->getParameter("output_etu_rn") : $this->getParameter("output_etu_attest");

		$bddData = $repo->findLastDataByMode($mode, $this->getUser()->getUsername());

		$students = $this->session->get('students');

		foreach ($students as $stud) {

			if (isset($ids) && $ids != null && in_array($stud->getNumero(), $ids))
				continue;

			$this->send_mail($stud, $mode, $bddData, $twig, $mailer);
		}
		$this->finder->deleteDirectory($etu . $this->getUser()->getUsername() . '.etu');
		return new JsonResponse();
	}

	private function send_mail(Student $stud, int $mode, ImportedData $bddData, Environment $twig, MailerInterface $mailer)
	{
		if (empty($stud->getMail()))
			return;

		$body = $twig->render('Mail/add_doc_mail.html.twig', [
			'stud' => $stud,
			'mode' => $mode,
			'bddData' => $bddData
		]);

		$to = $this->params->get('kernel.environment') == "dev" ? $this->getUser()->getExtraFields()["mail"] : $stud->getMail();

		$msg = (new Email())
			->from($this->getParameter('mail_sender'))
			->to($to)
			->subject($this->getParameter('mail_subject'))
			->html($body);

		$mailer->send($msg);
	}
}