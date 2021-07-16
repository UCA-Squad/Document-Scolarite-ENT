<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Entity\Student;
use App\Logic\CustomFinder;
use App\Logic\DocapostFast;
use App\Logic\FileAccess;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
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
	private $secu;

	public function __construct(FileAccess $file_access, CustomFinder $finder, ParameterBagInterface $params, DocapostFast $docapost, Security $secu)
	{
		$this->file_access = $file_access;
		$this->finder = $finder;
		$this->params = $params;
		$this->docapost = $docapost;
		$this->secu = $secu;
	}

	/**
	 * @Route("/releves", name="transfert_rn")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function transfert_rn(Request $request): JsonResponse
	{
		$ids = $request->get("ids");

		if ($this->transfert(ImportedData::RN, $ids))
			$this->addFlash("success", 'Les relevés de notes ont été transférés dans les dossiers étudiants');
		else
			$this->addFlash("error", 'Une erreur est survenue lors du transfert des relevés de notes dans les dossiers étudiants');
		return new JsonResponse('Les relevés de notes ont été transférés dans les dossiers étudiants');
	}

	/**
	 * @Route("/attests", name="transfert_attest")
	 * @param Request $request
	 * @param FileAccess $file_access
	 * @return JsonResponse
	 */
	public function transfert_attest(Request $request, FileAccess $file_access): JsonResponse
	{
		$ids = $request->get("ids");

		if ($this->transfert(ImportedData::ATTEST, $ids))
			$this->addFlash("success", 'Les attestations de réussite ont été transférées dans les dossiers étudiants');
		else
			$this->addFlash("error", 'Une erreur est survenue lors du transfert des attestations de réussite dans les dossiers étudiants');
		return new JsonResponse('Les attestations de réussite ont été transférées dans les dossiers étudiants');
	}

	private function transfert(int $mode, array $ids = null): bool
	{
		$from = $this->file_access->getTmpByMode($mode);
		$to = $this->file_access->getDirByMode($mode);
		$username = $this->secu->getUser()->getUsername();

		try {
			$process = new Process(['php', 'bin/console', 'transfert', $mode, $from, $to, $username, $ids], '/var/www/html/Document-Scolarite-ENT');
			$process->run();
		} catch (\Exception $e) {
			return false;
		}

		return true;
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
	public function send_mails(Request $request, IEtuParser $parser, MailerInterface $mailer, Environment $twig, ImportedDataRepository $repo): JsonResponse
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