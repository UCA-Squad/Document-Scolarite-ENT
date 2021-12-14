<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PDF;
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
	 * @param FileAccess $file_access
	 * @return Response
	 */
	public function setup_images(Request $request, FileAccess $file_access): Response
	{
		$mode = $request->get('mode');

		$pdf_folder = $file_access->getTamponFolder();

		$image = new UploadedFile($pdf_folder . $file_access->getTamponByMode($mode, 'f'), 'tampon_rn.png');
		$pdf = new UploadedFile($pdf_folder . $file_access->getPdfTamponByMode($mode, 'f'), 'pdf.pdf');
		return $this->render('setup_images.html.twig', [
			'image' => $image,
			'pdf' => $pdf,
			'mode' => $mode,
			'user' => $this->getUser()->getUsername()
		]);
	}

	/**
	 * Enregistre dans la session la position du tampon.
	 * @param Request $request
	 * @param SessionInterface $session
	 * @return JsonResponse
	 * @Route("/apply_images", name="apply_images")
	 */
	public function apply_images(Request $request, SessionInterface $session): JsonResponse
	{
		$tampon_position = $request->get('tampon');

		if (!isset($tampon_position))
			return new JsonResponse("Missing variable", 404);

		$session->set('tampon', $tampon_position);

		return new JsonResponse();
	}

	/**
	 * Supprime le pdf de tamponnage, le tampon, le pdf complet
	 * @Route("/cancel_images", name="cancel_images")
	 * @param Request $request
	 * @param CustomFinder $finder
	 * @param FileAccess $file_access
	 * @param SessionInterface $session
	 * @return RedirectResponse
	 */
	public function cancel(Request $request, CustomFinder $finder, FileAccess $file_access, SessionInterface $session): RedirectResponse
	{
		$mode = $request->get('mode');

		ImportController::clearCache($session, $file_access, $mode);
		$this->clearTamponFiles($file_access, $finder, $session, $mode);

		return $this->redirectToRoute($mode == ImportedData::RN ? 'import_rn' : 'import_attests');
	}

	/**
	 * Supprime le tampon et le pdf de tamponnage.
	 * @param FileAccess $file_access
	 * @param CustomFinder $finder
	 * @param SessionInterface $session
	 * @param int $mode
	 */
	public static function clearTamponFiles(FileAccess $file_access, CustomFinder $finder, SessionInterface $session, int $mode)
	{
//		$session->remove('tampon'); Besoin pour afficher la reconstruction du document ou non
		$tampon_image = $file_access->getTamponByMode($mode);
		$tampon_pdf = $file_access->getPdfTamponByMode($mode);
		$tampon_folder = $file_access->getTamponFolder();

		if (file_exists($tampon_image)) unlink($tampon_image);
		if (file_exists($tampon_pdf)) unlink($tampon_pdf);
		// Si dossier vide on supprime le dossier de tamponnage
		if (empty($finder->getFilesName($tampon_folder)))
			$finder->deleteDirectory($tampon_folder);
	}

}