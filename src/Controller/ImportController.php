<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Exception\ImportException;
use App\Form\ImportType;
use App\Logic\CustomFinder;
use App\Logic\PDF;
use App\Parser\EtuParser;
use App\Parser\IEtuParser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
	/**
	 * Check if files from previous selection exists.
	 * @param string $tmp_folder
	 * @param string $etu_folder
	 * @return bool
	 */
	private function selection_check(string $tmp_folder, string $etu_folder): bool
	{
		$finder = new CustomFinder();

		if (file_exists($etu_folder . $this->getUser()->getUsername() . '.etu')) {
			if (is_dir($tmp_folder . $this->getUser()->getUsername())) {
				if (!empty($finder->getDirsName($tmp_folder . $this->getUser()->getUsername()))) {
					return true;
				}
				$finder->deleteDirectory($tmp_folder . $this->getUser()->getUsername());
			}
			unlink($etu_folder . $this->getUser()->getUsername() . '.etu');
		}
		return false;
	}

	/**
	 * @Route("/releves", name="import_rn")
	 * @param Request $request
	 * @param IEtuParser $parser
	 * @return RedirectResponse|Response
	 * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
	 * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
	 * @throws \setasign\Fpdi\PdfReader\PdfReaderException
	 */
	public function import_rn(Request $request, IEtuParser $parser, PDF $pdfTool)
	{
		$tmp_folder = $this->getParameter("output_tmp_rn");
		$etu_folder = $this->getParameter("output_etu_rn");

		if ($this->selection_check($tmp_folder, $etu_folder))
			return $this->redirectToRoute('selection_rn');

		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::IMPORT, "type" => ImportType::RELEVE]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$pdfTool->setupRn();
			try {
				$this->import_process($pdfTool, $data, $parser, $tmp_folder, $etu_folder);
				return $this->noCacheResponse($this->redirectToRoute('selection_rn'));
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
	 * Transform the response into a no cache response.
	 * @param Response $response
	 * @return Response
	 */
	private function noCacheResponse(Response $response): ?Response
	{
		if (!$response)
			return null;
		$response->setMaxAge(0);
		$response->headers->addCacheControlDirective('must-revalidate');
		$response->headers->addCacheControlDirective('no-store');
		$response->headers->addCacheControlDirective('no-cache');
		return $response;
	}

	/**
	 * @Route("/attests", name="import_attests")
	 * @param Request $request
	 * @param EtuParser $parser
	 * @return RedirectResponse|Response
	 * @throws \Exception
	 */
	public function import_attests(Request $request, IEtuParser $parser, PDF $pdfTool)
	{
		$tmp_folder = $this->getParameter("output_tmp_attest");
		$etu_folder = $this->getParameter("output_etu_attest");

		if ($this->selection_check($tmp_folder, $etu_folder))
			return $this->redirectToRoute('selection_attests');

		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::IMPORT, "type" => ImportType::ATTEST]);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$pdfTool->setupAttest();
			try {
				$this->import_process($pdfTool, $data, $parser, $tmp_folder, $etu_folder);
				return $this->noCacheResponse($this->redirectToRoute('selection_attests'));
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
	 * @param IEtuParser $parser
	 * @param string $tmp_folder
	 * @param string $etu_folder
	 * @return bool
	 * @throws ImportException
	 */
	private function import_process(PDF $pdfTool, ImportedData $data, IEtuParser $parser, string $tmp_folder, string $etu_folder)
	{
		// Rewrite the pdf file with GhostScript to use it with pdf lib
		$gsPDF = $this->rewritePdf($data->getPdf()); //$data->getPdf();
		if ($gsPDF == "")
			throw new ImportException("L'application n'a pas réussi à convertir le fichier " . $data->getPdf()->getClientOriginalName());

		// Parse the file into Student array with the defined normalizer service
		$etu = $parser->parseETU($data->getEtu());

		// Move the file into the defined location
		$data->getEtu()->move($etu_folder, $this->getUser()->getUsername() . '.etu');

		// Index process to handle pagination
		$indexes = $pdfTool->indexPages($parser, $gsPDF, $etu);

		if ($indexes === false) {
			unlink($etu_folder . $this->getUser()->getUsername() . '.etu');
			throw new ImportException("L'application n'a pas réussi à extraire les informations d'un ou plusieurs étudiant(s)");
		}

		$data->LoadStudentData($etu[0], $indexes['date'], count($etu), $this->getUser()->getUsername());

		$em = $this->getDoctrine()->getManager();

		$bddData = $em->getRepository(ImportedData::class)->findOneBy(['pdf_filename' => $data->getPdfFilename(), 'etu_filename' => $data->getEtuFilename()]);

		if ($bddData != null)
			$bddData->addHistory(new History(0));
		$em->persist($bddData == null ? $data : $bddData);
		$em->flush();

		$pdfTool->truncateFile($parser, $gsPDF, $data, $tmp_folder . $this->getUser()->getUsername() . '/', $indexes, $etu);
		return true;
	}

	/**
	 * Rewrite correctly the uploaded pdf and return the new file's path.
	 * @param UploadedFile $pdf The uploaded pdf
	 * @return string The rewrited pdf path
	 */
	private function rewritePdf(UploadedFile $pdf): string
	{
		$path = $pdf->getPathname();
		$new_path = preg_replace('/' . $pdf->getFilename() . '/', "GS" . str_replace('.tmp', '.pdf', $pdf->getFilename()), $path);

		$cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' '" . $path . "'";
		try {
			$proc = Process::fromShellCommandline($cmd);
			$proc->run(null, []);
		} catch (\Exception $e) {
			return "";
		}
		return $new_path;
	}
}