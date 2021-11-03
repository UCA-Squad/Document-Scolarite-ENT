<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PDF;
use App\Logic\PdfResponse;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Doctrine\ORM\NonUniqueResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/selection")
 * @IsGranted("ROLE_SCOLA")
 */
class SelectionController extends AbstractController
{
	private $file_access;
	private $finder;

	public function __construct(FileAccess $file_access, CustomFinder $finder)
	{
		$this->file_access = $file_access;
		$this->finder = $finder;
	}

	/**
	 * @Route("/releves", name="selection_rn")
	 * @Cache(vary={"no-cache", "must-revalidate", "no-store"})
	 * @param IEtuParser $parser
	 * @param ImportedDataRepository $repo
	 * @param SessionInterface $session
	 * @return RedirectResponse|Response
	 * @throws NonUniqueResultException
	 */
	public function selection_rn(IEtuParser $parser, ImportedDataRepository $repo, SessionInterface $session)
	{
		$redirect = $this->selection(ImportedData::RN);
		if ($redirect)
			return $this->redirectToRoute('import_rn');

		$tampon = $session->get('tampon') !== null ?? false;
		$bddData = $repo->findLastRnData($this->getUser()->getUsername());
		$etu = $this->LoadEtu($parser, ImportedData::RN);

		return $this->render('releve_notes/selection.html.twig', ['students' => $etu, 'bddData' => $bddData, 'mode' => ImportedData::RN, 'tampon' => $tampon]);
	}

	/**
	 * @Route("/attests", name="selection_attests")
	 * @param IEtuParser $parser
	 * @param ImportedDataRepository $repo
	 * @param SessionInterface $session
	 * @return RedirectResponse|Response
	 * @throws NonUniqueResultException
	 */
	public function selection_attests(IEtuParser $parser, ImportedDataRepository $repo, SessionInterface $session)
	{
		$redirect = $this->selection(ImportedData::ATTEST);
		if ($redirect)
			return $this->redirectToRoute('import_attests');

		$tampon = $session->get('tampon') !== null ?? false;
		$bddData = $repo->findLastAttestData($this->getUser()->getUsername());
		$etu = $this->LoadEtu($parser, ImportedData::ATTEST);

		return $this->render('releve_notes/selection.html.twig', ['students' => $etu, 'bddData' => $bddData, 'mode' => ImportedData::ATTEST, 'tampon' => $tampon]);
	}

	private function selection(int $mode): bool
	{
		if (file_exists($this->file_access->getEtuByMode($mode)) && !empty($this->finder->getFilesName($this->file_access->getTmpByMode($mode))))
			return false;
		$this->clearTmpFiles($mode);
		return true;
	}

	private function LoadEtu(IEtuParser $parser, int $mode): array
	{
		$etu = $parser->parseETU($this->file_access->getEtuByMode($mode));

		foreach ($etu as $entry) {
			$entry->LoadFile($this->file_access->getTmpByMode($mode), $this->file_access->getDirByMode($mode));
		}
		return $etu;
	}

	/**
	 * @Route("/cancel/releves", name="cancel_rn")
	 * @param ImportedDataRepository $repo
	 * @return RedirectResponse
	 * @throws NonUniqueResultException
	 */
	public function cancel_rn(ImportedDataRepository $repo): RedirectResponse
	{
		$data = $repo->findLastRnData($this->getUser()->getUsername());

		$this->cancel(ImportedData::RN, $data);
		return $this->redirectToRoute('import_rn');
	}

	/**
	 * @Route("/cancel/attests", name="cancel_attest")
	 * @param ImportedDataRepository $repo
	 * @return RedirectResponse
	 * @throws NonUniqueResultException
	 */
	public function cancel_attest(ImportedDataRepository $repo): RedirectResponse
	{
		$data = $repo->findLastAttestData($this->getUser()->getUsername());

		$this->cancel(ImportedData::ATTEST, $data);
		return $this->redirectToRoute('import_attests');
	}

	/**
	 * Supprime le dossier des pdfs temporaires et le fichier .etu
	 * @param int $mode
	 */
	private function clearTmpFiles(int $mode)
	{
		$etu = $this->file_access->getEtuByMode($mode);
		$tmp = $this->file_access->getTmpByMode($mode);

		if (file_exists($etu)) unlink($etu);

		$this->finder->deleteDirectory($tmp);
	}

	private function cancel(int $mode, ImportedData $data = null)
	{
		$this->clearTmpFiles($mode);

		if ($data == null)
			return;

		$em = $this->getDoctrine()->getManager();

		if (count($data->getHistory()) <= 1)    // If count histo == 1 => 1rst import
			$em->remove($data);
		else                                  // else réimport
			$em->remove($data->getLastHistory());
		$em->flush();
	}

	/**
	 * @Route("/rebuild", name="rebuild_doc")
	 */
	public function reBuild(Request $request, IEtuParser $parser, CustomFinder $finder, SessionInterface $session): JsonResponse
	{
		$mode = $request->get('mode');
		$folder = $this->file_access->getTmpByMode($mode);
		$new_path = $folder . 'rebuild.pdf';

		$etu = $parser->parseETU($this->file_access->getEtuByMode($mode));
		$transfered = $this->getEtuTransfered($session->get('transfered'), $etu);

		// Trie des étudiants par nom,prenom
		usort($transfered, function ($a, $b) {
			$cmpNom = strcmp($a[0]->getName(), $b[0]->getName());
			$cmpPrenom = strcmp($a[0]->getSurname(), $b[0]->getSurname());
			return $cmpNom == 0 ? $cmpPrenom : $cmpNom;
		});

		$cmd = "gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' ";
		foreach ($transfered as $key => $transfer) {
			$filepath = str_replace(' ', "\ ", $transfer[1]);
			$filepath = str_replace('(', "\(", $filepath);
			$filepath = str_replace(')', "\)", $filepath);
			$cmd .= $filepath . " ";
		}

		try {
			$proc = Process::fromShellCommandline($cmd);
			$proc->setTimeout(null);
			$proc->setIdleTimeout(null);
			$proc->run();

			$index = $this->finder->getFileIndex($folder, "rebuild.pdf");
			return new JsonResponse(['index' => $index], 200);
		} catch (\Exception $e) {
			return new JsonResponse(['index' => -1], 500);
		}
	}

	/**
	 * Map les documents transférés à l'étudiant correspondant.
	 * @param array $transfered
	 * @param array $studs
	 * @return array
	 */
	private function getEtuTransfered(array $transfered, array $studs): array
	{
		$res = [];
		foreach ($studs as $stud) {
			foreach ($transfered as $transfert) {
				if (str_contains($transfert, $stud->getNumero())) {
					$res[$stud->getNumero()] = [$stud, $transfert];
					break;
				}
			}
		}
		return $res;
	}

	/**
	 * @Route("/rebuild/{mode}", name="get_rebuilded_doc")
	 * @param int $mode
	 * @param Request $request
	 * @return BinaryFileResponse|Response
	 */
	public function get_rebuilded_doc(int $mode, Request $request)
	{
		$folder = $this->file_access->getTmpByMode($mode);
		$index = $this->finder->getFileIndex($folder, "rebuild.pdf");

		return PdfResponse::getPdfResponse($index, $folder);
	}
}