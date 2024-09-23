<?php


namespace App\Controller;


use App\Logic\FileAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api'), IsGranted('ROLE_SCOLA')]
class TamponController extends AbstractController
{
    #[Route('/get_tampon/{mode}', name: 'api_get_tampon')]
    public function api_get_tampon(int $mode, FileAccess $file_access): BinaryFileResponse
    {
        return $this->file($file_access->getTamponByMode($mode));
    }

    #[Route('/get_tampon_example/{mode}', name: 'api_get_tampon_example')]
    public function api_get_tampon_example(int $mode, FileAccess $file_access): BinaryFileResponse
    {
        return $this->file($file_access->getPdfTamponByMode($mode));
    }

    /**
     * Enregistre dans la session la position du tampon.
     */
    #[Route('/apply_tampon', name: 'apply_images')]
    public function apply_images(Request $request, SessionInterface $session): JsonResponse
    {
        $position = json_decode($request->getContent(), true);

        if (!isset($position))
            return new JsonResponse("Missing variable", 404);

        $tampon_position = [
            'x' => $position['dx'],
            'y' => $position['dy']
        ];

        $session->set('tampon', $tampon_position);

        return new JsonResponse();
    }

}