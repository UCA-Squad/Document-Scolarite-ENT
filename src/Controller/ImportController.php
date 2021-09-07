<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Exception\ImportException;
use App\Form\ImportType;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PDF;
use App\Parser\EtuParser;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/import")
 * @IsGranted("ROLE_SCOLA")
 */
class ImportController extends AbstractController
{
	private $file_acess;
	private $parser;

	public function __construct(FileAccess $file_acess, IEtuParser $parser)
	{
		$this->file_acess = $file_acess;
		$this->parser = $parser;
	}

	/**
	 * Check if files from previous selection exists.
	 * @param int $mode
	 * @return RedirectResponse|null
	 */
	private function selection_check(int $mode): ?RedirectResponse
	{
		$tmp_folder = $this->file_acess->getTmpByMode($mode);
		$etu_file = $this->file_acess->getEtuByMode($mode);
		$pdf_file = $this->file_acess->getPdfByMode($mode);

		$tampon_folder = $this->file_acess->getTamponFolder();
		$tampon_image = $this->file_acess->getTamponByMode($mode);
		$tampon_pdf = $this->file_acess->getPdfTamponByMode($mode);

		$finder = new CustomFinder();

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
	 * @param IEtuParser $parser
	 * @param PDF $pdfTool
	 * @param FileAccess $file_access
	 * @return RedirectResponse|Response
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	public function import_rn(Request $request, IEtuParser $parser, PDF $pdfTool, FileAccess $file_access)
	{
		$redirect = $this->selection_check(ImportedData::RN);
		if (isset($redirect)) return $redirect;

		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::IMPORT, "type" => ImportType::RELEVE]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$tampon_img = $form->get('tampon')->getData();
			$pdfTool->setupRn();
			try {
				$infos = $this->import_process($pdfTool, $data, ImportedData::RN, $tampon_img);
				if (!$infos)
					return $this->redirectToRoute('selection_rn');
				$solo_index = $this->extractFirstIndex($infos, $form->get('num_page')->getData());
				$pdfTool->truncateFile($parser, $infos["gsPdf"], $data, $file_access->getPdfTamponRn('d'), $solo_index, $infos["etu"], true);
				return $this->redirectToRoute('setup_images', ['mode' => ImportedData::RN]);
			} catch (ImportException $e) {
				$error = $e->getMessage();
			}
		}

		return $this->render('releve_notes/index.html.twig', [
			'form' => $form->createView(),
			'cancel' => isset($error) ? $error : null,
		]);
	}

	/**
	 * @Route("/attests", name="import_attests")
	 * @param Request $request
	 * @param IEtuParser $parser
	 * @param PDF $pdfTool
	 * @param FileAccess $file_access
	 * @return RedirectResponse|Response
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	public function import_attests(Request $request, IEtuParser $parser, PDF $pdfTool, FileAccess $file_access)
	{
		$redirect = $this->selection_check(ImportedData::ATTEST);
		if (isset($redirect))
			return $redirect;

		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::IMPORT, "type" => ImportType::ATTEST]);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$tampon_img = $form->get('tampon')->getData();
			$pdfTool->setupAttest();
			try {
				$infos = $this->import_process($pdfTool, $data, ImportedData::ATTEST, $tampon_img);
				if (!$infos)
					return $this->redirectToRoute('selection_attests');
				$solo_index = $this->extractFirstIndex($infos, $form->get('num_page')->getData());
				$pdfTool->truncateFile($parser, $infos["gsPdf"], $data, $this->getUser()->getUsername() . '/', $solo_index, $infos["etu"], true);
				return $this->redirectToRoute('setup_images', ['mode' => ImportedData::ATTEST]);
			} catch (ImportException $e) {
				$error = $e->getMessage();
			}
		}

		return $this->render('attestations/index.html.twig', [
			'form' => $form->createView(),
			'cancel' => isset($error) ? $error : null
		]);
	}

	/**
	 * @param PDF $pdfTool
	 * @param ImportedData $data
	 * @param int $mode
	 * @param UploadedFile|null $tampon_img
	 * @return array|null
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws ImportException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	private function import_process(PDF $pdfTool, ImportedData $data, int $mode, UploadedFile $tampon_img = null): ?array
	{
		// Rewrite the pdf file with GhostScript to use it with pdf lib
		$gsPDF = $this->rewritePdf($data->getPdf(), $mode);
		if ($gsPDF == "")
			throw new ImportException("L'application n'a pas réussi à convertir le fichier " . $data->getPdf()->getClientOriginalName());

		// Parse the file into Student array with the defined normalizer service
		$etu = $this->parser->parseETU($data->getEtu());

		// Move the file into the defined location
		$data->getEtu()->move($this->file_acess->getEtuByMode($mode, 'd'), $this->file_acess->getEtuByMode($mode, 'f'));

		// Images
		if (isset($tampon_img)) {
//			dd($this->file_acess->getTamponByMode($mode, 'd'));
			$tampon_img->move($this->file_acess->getTamponByMode($mode, 'd'), $this->file_acess->getTamponByMode($mode, 'f'));
		}

		// Index process to handle pagination
		$indexes = $pdfTool->indexPages($this->parser, $gsPDF, $etu);

		if ($indexes === false) {
			unlink($this->file_acess->getEtuByMode($mode));
			throw new ImportException("L'application n'a pas réussi à extraire les informations d'un ou plusieurs étudiant(s)");
		}

		$data->LoadStudentData($etu[0], $indexes['date'], count($etu), $this->getUser()->getUsername());

		$this->updateData($data);

		if (!isset($tampon_img))
			$pdfTool->truncateFile($this->parser, $gsPDF, $data, $this->file_acess->getTmpByMode($mode), $indexes, $etu);

		return isset($tampon_img) ? [
			"gsPdf" => $gsPDF,
			"indexes" => $indexes,
			"etu" => $etu
		] : null;
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
	 * @param array $infos
	 * @param int $num_page
	 * @return array
	 */
	private function extractFirstIndex(array $infos, int $num_page): array
	{
		$max = count($infos["indexes"]["indexes"]);
		$max += array_key_first($infos["indexes"]["indexes"]) - 1;

		if ($num_page > $max)
			$num_page = $max;

		// Si la numérotation commence apres num_page
		if ($num_page < array_key_first($infos["indexes"]["indexes"]))
			$num_page = array_key_first($infos["indexes"]["indexes"]);

		return $solo_index = [
			"date" => $infos["indexes"]["date"],
			"indexes" => [
				"$num_page" => $infos["indexes"]["indexes"][$num_page]
			]
		];
	}

	/**
	 * Rewrite correctly the uploaded pdf and return the new file's path.
	 * @param UploadedFile $pdf The uploaded pdf
	 * @param int $mode
	 * @return string The rewrited pdf path
	 */
	private function rewritePdf(UploadedFile $pdf, int $mode): string
	{
		$new_path = $this->file_acess->getPdfByMode($mode);

		$cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' '" . $pdf->getPathname() . "'";
		try {
			$proc = Process::fromShellCommandline($cmd);
			$proc->setTimeout(null);
			$proc->setIdleTimeout(null);
			$proc->run();
		} catch (\Exception $e) {
			return "";
		}
		return $new_path;
	}
}