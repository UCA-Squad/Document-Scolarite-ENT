<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Exception\ImportException;
use App\Form\ImportType;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PDF;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/import")
 * @IsGranted("ROLE_SCOLA")
 */
class ImportController extends AbstractController
{
	private $file_access;
	private $parser;
	private $session;

	public function __construct(FileAccess $file_access, IEtuParser $parser, SessionInterface $session)
	{
		$this->file_access = $file_access;
		$this->parser = $parser;
		$this->session = $session;
	}

	/**
	 * Check if files from previous selection exists.
	 * @param int $mode
	 * @return RedirectResponse|null
	 */
	private function selection_check(int $mode): ?RedirectResponse
	{
		$tmp_folder = $this->file_access->getTmpByMode($mode);
		$etu_file = $this->file_access->getEtuByMode($mode);
		$pdf_file = $this->file_access->getPdfByMode($mode);

		$tampon_folder = $this->file_access->getTamponFolder();
		$tampon_image = $this->file_access->getTamponByMode($mode);
		$tampon_pdf = $this->file_access->getPdfTamponByMode($mode);

		$finder = new CustomFinder();

		$this->session->remove('tampon');
		$this->session->remove('transfered');

		// Tamponnage vérif
		if (is_dir($tampon_folder) && file_exists($tampon_image) && file_exists($tampon_pdf) && file_exists($pdf_file))
			return $this->redirectToRoute('setup_images', ['mode' => $mode]);
		else {
			if (file_exists($pdf_file)) unlink($pdf_file);
			if (file_exists($tampon_image)) unlink($tampon_image);
			if (file_exists($tampon_pdf)) unlink($tampon_pdf);
		}

		// Selection vérif
		if (file_exists($etu_file) && !empty($finder->getDirsName($tmp_folder)))
			return $mode == ImportedData::RN ? $this->redirectToRoute('selection_rn') : $this->redirectToRoute('selection_attests');
		else {
			$finder->deleteDirectory($tmp_folder);
			if (file_exists($etu_file)) unlink($etu_file);
		}

		return null;
	}

	/**
	 * @Route("/releves", name="import_rn")
	 * @param Request $request
	 * @param PDF $pdfTool
	 * @return RedirectResponse|Response
	 */
	public function import_rn(Request $request, PDF $pdfTool): Response
	{
		return $this->import_generique($request, $pdfTool, ImportedData::RN);
	}

	/**
	 * @Route("/attests", name="import_attests")
	 * @param Request $request
	 * @param PDF $pdfTool
	 * @return Response
	 */
	public function import_attests(Request $request, PDF $pdfTool): Response
	{
		return $this->import_generique($request, $pdfTool, ImportedData::ATTEST);
	}

	/**
	 * @Route("/truncate_unit", name="truncate_by_unit")
	 */
	public function truncateByUnit(Request $request, PDF $pdfTool, ImportedDataRepository $repo): JsonResponse
	{
		$mode = $request->get('mode');
		$page = $request->get('page');

		if (!isset($mode) || !isset($page))
			return new JsonResponse("Paramètre incomplet", 404);

		$tampon_position = $this->session->get('tampon');
		if (isset($tampon_position)) {
			$pdfTool->setupPosition($tampon_position['x'], $tampon_position['y']);
			$pdfTool->setupImage($this->file_access->getTamponByMode($mode));
		}

		$username = $this->getUser()->getUsername();
		$importedData = $repo->findLastDataByMode($mode, $username);

		$mode == ImportedData::RN ? $pdfTool->setupRn() : $pdfTool->setupAttest();

		$tmp_folder = $this->file_access->getTmpByMode($mode);
		$indexes = $this->session->get('indexes');
		$etu = $this->session->get('students');

		$page = $pdfTool->truncateFileByPage($this->file_access->getPdfByMode($mode), $importedData, $tmp_folder, $indexes, $etu, $page);

		return new JsonResponse($page);
	}

	private function import_generique(Request $request, PDF $pdfTool, int $mode): Response
	{
		$redirect = $this->selection_check($mode);
		if (isset($redirect)) return $redirect;

		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::IMPORT, "type" => $mode == ImportedData::RN ? ImportType::RELEVE : ImportType::ATTEST]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$mode == ImportedData::RN ? $pdfTool->setupRn() : $pdfTool->setupAttest();
			$res = $this->import($mode, $pdfTool, $data, $form);
			if (isset($res))
				return $res;
			return $this->redirectToRoute('truncate', ['mode' => $mode]);
		}

		return $this->render('releve_notes/index.html.twig', [
			'form' => $form->createView(),
			'cancel' => $error ?? null,
			'mode' => $mode,
		]);
	}

	/**
	 * @Route("/truncate/{mode}", name="truncate")
	 */
	public function truncate(int $mode, PDF $pdfTool): Response
	{
		$pageCount = $pdfTool->getPageCount($this->file_access->getPdfByMode($mode));
		$pageFirst = $this->session->get('indexes') !== null ? array_key_first($this->session->get('indexes')['indexes']) : null;

		return $this->render('truncate.html.twig', [
			'mode' => $mode,
			'pageCount' => $pageCount,
			'pageFirst' => $pageFirst
		]);
	}

	private function import(int $mode, PDF $pdfTool, ImportedData $data, FormInterface $form): ?Response
	{
		try {
			$this->import_process($pdfTool, $data, $mode, $form->get('tampon')->getData());

			if ($form->get('tampon')->getData() !== null) {
				$solo_index = $this->extractFirstIndex($this->session->get('indexes'), $form->get('num_page')->getData());
				$pdfTool->truncateFile($this->file_access->getPdfByMode($mode), $data, $this->getUser()->getUsername() . '/', $solo_index, $this->session->get('students'),
					true);
				return $this->redirectToRoute('setup_images', ['mode' => $mode]);
			}

		} catch (ImportException | PdfParserException | PdfReaderException $e) {
			return null;
		}
		return null;
	}

	/**
	 * Traitement commun : initialise les données.
	 * @param PDF $pdfTool
	 * @param ImportedData $data
	 * @param int $mode
	 * @param UploadedFile|null $tampon_img
	 * @return void
	 * @throws ImportException
	 */
	private function import_process(PDF $pdfTool, ImportedData $data, int $mode, UploadedFile $tampon_img = null)
	{
		// Rewrite the pdf file with GhostScript to use it with pdf lib
		if (!$this->rewritePdf($data->getPdf(), $mode))
			throw new ImportException("L'application n'a pas réussi à convertir le fichier " . $data->getPdf()->getClientOriginalName());

		// Parse the file into Student array with the defined normalizer service
		$etu = $this->parser->parseETU($data->getEtu());
		$this->session->set('students', $etu);
		// Move the file into the defined location
		$data->getEtu()->move($this->file_access->getEtuByMode($mode, 'd'), $this->file_access->getEtuByMode($mode, 'f'));

		// Images
		if (isset($tampon_img)) {
			$tampon_img->move($this->file_access->getTamponByMode($mode, 'd'), $this->file_access->getTamponByMode($mode, 'f'));
		}

		// Index process to handle pagination
		$indexes = $pdfTool->indexPages($this->file_access->getPdfByMode($mode), $etu);
		$this->session->set('indexes', $indexes);

		if ($indexes === false) {
			unlink($this->file_access->getEtuByMode($mode));
			throw new ImportException("L'application n'a pas réussi à extraire les informations d'un ou plusieurs étudiant(s)");
		}

		$data->LoadStudentData($etu[0], $indexes['date'], count($etu), $this->getUser()->getUsername());

		$this->updateData($data);
	}

	/**
	 * Crée en base une entrée pour les nouvelles donnéees ou met à jour l'existante avec un pré-historique.
	 * @param ImportedData $data
	 */
	private function updateData(ImportedData $data)
	{
		$em = $this->getDoctrine()->getManager();

		$bddData = $em->getRepository(ImportedData::class)->findOneBy(['pdf_filename' => $data->getPdfFilename(), 'etu_filename' => $data->getEtuFilename()]);

		if ($bddData != null)
			$bddData->addHistory(new History(0));
		$em->persist($bddData == null ? $data : $bddData);
		$em->flush();
	}

	/**
	 * Extrait un index
	 * @param array $indexes
	 * @param int $num_page
	 * @return array
	 */
	private function extractFirstIndex(array $indexes, int $num_page): array
	{
		$max = count($indexes['indexes']);
		$max += array_key_first($indexes['indexes']) - 1;

		if ($num_page > $max)
			$num_page = $max;

		// Si la numérotation commence apres num_page
		if ($num_page < array_key_first($indexes['indexes']))
			$num_page = array_key_first($indexes['indexes']);

		return [
			"date" => $indexes['date'],
			"indexes" => [
				"$num_page" => $indexes['indexes'][$num_page]
			]
		];
	}

	/**
	 * Réécris le document PDF avec la librairie GhostScript.
	 * @param UploadedFile $pdf Le document PDF.
	 * @param int $mode
	 * @return bool Succès ou Échec.
	 */
	private function rewritePdf(UploadedFile $pdf, int $mode): bool
	{
		$new_path = $this->file_access->getPdfByMode($mode);

		$cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' '" . $pdf->getPathname() . "'";
		try {
			$proc = Process::fromShellCommandline($cmd);
			$proc->setTimeout(null);
			$proc->setIdleTimeout(null);
			$proc->run();
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Supprime les fichiers et données mis en cache.
	 */
	public static function clearCache(SessionInterface $session, FileAccess $file_access, int $mode)
	{
		$session->remove('students');
		$session->remove('indexes');

		$pdf_file = $file_access->getPdfByMode($mode);
		if (file_exists($pdf_file)) unlink($pdf_file);

		$etu_file = $file_access->getEtuByMode($mode);
		if (file_exists($etu_file)) unlink($etu_file);
	}
}