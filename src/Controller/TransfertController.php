<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Entity\Student;
use App\Events\Events;
use App\Logic\CustomFinder;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
	/**
	 * @Route("/releves", name="transfert_rn")
	 * @param Request $request
	 * @param EventDispatcherInterface $eventDispatcher
	 * @return JsonResponse
	 */
	public function transfert_rn(Request $request, EventDispatcherInterface $eventDispatcher)
	{
		$ids = $request->get("ids");

		$tmp = $this->getParameter("output_tmp_rn");
		$dir = $this->getParameter("output_dir_rn");

		if ($this->transfert($tmp, $dir, $eventDispatcher, ImportedData::RN, $ids))
			$this->addFlash("success", 'Les relevés de notes ont été transférés dans les dossiers étudiants');
		else
			$this->addFlash("error", 'Une erreur est survenue lors du transfert des relevés de notes dans les dossiers étudiants');
		return new JsonResponse('Les relevés de notes ont été transférés dans les dossiers étudiants');
	}

	/**
	 * @Route("/attests", name="transfert_attest")
	 * @param Request $request
	 * @param EventDispatcherInterface $eventDispatcher
	 * @return JsonResponse
	 */
	public function transfert_attest(Request $request, EventDispatcherInterface $eventDispatcher)
	{
		$ids = $request->get("ids");

		$tmp = $this->getParameter("output_tmp_attest");
		$dir = $this->getParameter("output_dir_attest");

		if ($this->transfert($tmp, $dir, $eventDispatcher, ImportedData::ATTEST, $ids))
			$this->addFlash("success", 'Les attestations de réussite ont été transférées dans les dossiers étudiants');
		else
			$this->addFlash("error", 'Une erreur est survenue lors du transfert des attestations de réussite dans les dossiers étudiants');
		return new JsonResponse('Les attestations de réussite ont été transférées dans les dossiers étudiants');
	}

	private function transfert(string $from, string $to, EventDispatcherInterface $eventDispatcher, int $mode, array $ids = null): bool
	{
		$from .= $this->getUser()->getUsername() . '/';

		$finder = new CustomFinder();
		$documents = $finder->getDirsName($from);

		$docs_count = count($documents);
		if ($docs_count == 0)
			return false;

		if (!is_dir($to))
			mkdir($to);

		$not_transfered = 0;
		foreach ($documents as $doc) {
			if (isset($ids) && in_array($doc, $ids)) {
				// si pas séléctionné pour transfert mais déjà présent sur le serveur
				if (!file_exists($to . $doc . '/' . $finder->getFirstFile($from . $doc)))
					$not_transfered++;
				continue;
			}
			if (!is_dir($to . $doc))
				mkdir($to . $doc);
			$fileFrom = $finder->getFirstFile($from . $doc);
			$index = $finder->getFileIndex($to . $doc, $fileFrom);
			if ($index != -1) {
				unlink($to . $doc . '/' . $finder->getFileByIndex($to . $doc, $index));
			}
			rename($from . $doc . '/' . $fileFrom, $to . $doc . '/' . $fileFrom);
			$eventDispatcher->dispatch(new GenericEvent($doc, ['path' => $to . $doc . '/' . $fileFrom]), Events::STUDENT_TRANSFERED);
		}
		$finder->deleteDirectory($from);

		$this->update_transfered_files($mode, $docs_count, $not_transfered);

		return true;
	}

	/**
	 * Update the field 'nbFiles' of the last ImportedData.
	 * @param int $mode
	 * @param int $docs_count
	 * @param int $not_transfered
	 */
	private function update_transfered_files(int $mode, int $docs_count, int $not_transfered)
	{
		$data = $this->getDoctrine()->getRepository(ImportedData::class)->findLastDataByMode($mode, $this->getUser()->getUsername());

		$data->getLastHistory()->setNbFiles($docs_count - $not_transfered);
		$data->getLastHistory()->setState(History::Transfered);
		$data->getLastHistory()->setDate();

		$em = $this->getDoctrine()->getManager();
		$em->persist($data);
		$em->flush();
	}

	/**
	 * @Route("/mail", name="send_mails")
	 * @param Request $request
	 * @param IEtuParser $parser
	 * @param MailerInterface $mailer
	 * @param Environment $twig
	 * @param ImportedDataRepository $repo
	 * @return JsonResponse
	 */
	public function send_mails(Request $request, IEtuParser $parser, MailerInterface $mailer, Environment $twig, ImportedDataRepository $repo)
	{
		$ids = $request->get('ids');
		$mode = $request->get('mode');

		$etu = $mode == ImportedData::RN ? $this->getParameter("output_etu_rn") : $this->getParameter("output_etu_attest");

		$bddData = $repo->findLastDataByMode($mode, $this->getUser()->getUsername());

		$students = $parser->parseETU($etu . $this->getUser()->getUsername() . '.etu');

		foreach ($students as $stud) {

			if (isset($ids) && $ids != null && in_array($stud->getNumero(), $ids))
				continue;

			$this->send_mail($stud, $mode, $bddData, $twig, $mailer);
		}
		$finder = new CustomFinder();
		$finder->deleteDirectory($etu . $this->getUser()->getUsername() . '.etu');
		return new JsonResponse();
	}

	private function send_mail(Student $stud, int $mode, ImportedData $bddData, Environment $twig, MailerInterface $mailer)
	{
		$body = $twig->render('Mail/add_doc_mail.html.twig', [
			'stud' => $stud,
			'mode' => $mode,
			'bddData' => $bddData
		]);

		$msg = (new Email())
			->from($this->getParameter('mail_sender'))
			->to($stud->getMail())
			->subject($this->getParameter('mail_subject'))
			->html($body);

		$mailer->send($msg);
	}
}