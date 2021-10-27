<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PDF;
use App\Parser\EtuParser;
use App\Repository\ImportedDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TamponController
 * @package App\Controller
 */
class TamponController extends AbstractController
{
	/**
	 * @Route("/setup_images", name="setup_images")
	 * @param Request $request
	 * @param CustomFinder $finder
	 * @param FileAccess $file_acces
	 * @return Response
	 */
	public function setup_images(Request $request, CustomFinder $finder, FileAccess $file_acces): Response
	{
		$mode = $request->get('mode');

		$pdf_folder = $file_acces->getTamponFolder();

		$image = new UploadedFile($pdf_folder . $file_acces->getTamponByMode($mode, 'f'), 'tampon_rn.png');
		$pdf = new UploadedFile($pdf_folder . $file_acces->getPdfTamponByMode($mode, 'f'), 'pdf.pdf');
		return $this->render('setup_images.html.twig', [
			'image' => $image,
			'pdf' => $pdf,
			'mode' => $mode,
			'user' => $this->getUser()->getUsername()
		]);
	}

	/**
	 * @param Request $request
	 * @param PDF $pdfTool
	 * @param EtuParser $parser
	 * @param ImportedDataRepository $repo
	 * @param FileAccess $file_acces
	 * @return JsonResponse
	 * @Route("/apply_images", name="apply_images")
	 */
	public function apply_images(Request $request, PDF $pdfTool, EtuParser $parser, ImportedDataRepository $repo, FileAccess $file_acces, SessionInterface $session): JsonResponse
	{
		$tampon_position = $request->get('tampon');
		$mode = $request->get('mode');

		if (!isset($tampon_position) || !isset($mode))
			return new JsonResponse("Missing variable", 404);

		try {
			$etu_file = $file_acces->getEtuByMode($mode);
			$tmp_folder = $file_acces->getTmpByMode($mode);
			$gsPdf = $file_acces->getPdfByMode($mode);
			$data = $mode == 0 ? $repo->findLastRnData($this->getUser()->getUsername()) : $repo->findLastAttestData($this->getUser()->getUsername());

			$mode == ImportedData::RN ? $pdfTool->setupRn() : $pdfTool->setupAttest();
			$pdfTool->setupPosition($tampon_position['x'], $tampon_position['y']);
			$pdfTool->setupImage($file_acces->getTamponByMode($mode)); //
			$etu = $parser->parseETU($etu_file);
			$indexes = $pdfTool->indexPages($parser, $gsPdf, $etu);
			$pdfTool->truncateFile($parser, $gsPdf, $data, $tmp_folder, $indexes, $etu);

			$this->clearTamponFiles($file_acces, new CustomFinder(), $mode);
			$session->set('tampon', true);

		} catch (\Exception $e) {
			return new JsonResponse($e->getMessage(), 404);
		}

		return new JsonResponse();
	}

	/**
	 * Supprime le pdf de tamponnage, le tampon, le pdf complet
	 * @Route("/cancel_images", name="cancel_images")
	 * @param Request $request
	 * @param CustomFinder $finder
	 * @param FileAccess $file_access
	 * @return RedirectResponse
	 */
	public function cancel(Request $request, CustomFinder $finder, FileAccess $file_access): RedirectResponse
	{
		$mode = $request->get('mode');

		$this->clearTamponFiles($file_access, $finder, $mode);

		$etu_file = $file_access->getEtuByMode($mode);
		if (file_exists($etu_file)) unlink($etu_file);

		return $this->redirectToRoute($mode == ImportedData::RN ? 'import_rn' : 'import_attests');
	}

	/**
	 * Supprime le tampon image, le pdf cible et le pdf complet
	 * @param FileAccess $file_access
	 * @param CustomFinder $finder
	 * @param int $mode
	 */
	private function clearTamponFiles(FileAccess $file_access, CustomFinder $finder, int $mode)
	{
		$tampon_image = $file_access->getTamponByMode($mode);
		$tampon_pdf = $file_access->getPdfTamponByMode($mode);
		$tampon_folder = $file_access->getTamponFolder();
		$pdf_file = $file_access->getPdfByMode($mode);

		if (file_exists($pdf_file)) unlink($pdf_file);
		if (file_exists($tampon_image)) unlink($tampon_image);
		if (file_exists($tampon_pdf)) unlink($tampon_pdf);
		// Si dossier vide on supprime le dossier de tamponnage
		if (empty($finder->getFilesName($tampon_folder)))
			$finder->deleteDirectory($tampon_folder);
	}

}