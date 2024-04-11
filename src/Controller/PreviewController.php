<?php


namespace App\Controller;

use App\Logic\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/preview'), IsGranted('ROLE_SCOLA')]
class PreviewController extends AbstractController
{
    #[Route('/tmp/releves/{numero}', name: 'preview_tmp_rn')]
	public function preview_tmp_rn($numero): BinaryFileResponse|Response
    {
		return $this->watch($numero, 0, $this->getParameter("output_tmp_rn") . $this->getUser()->getUsername() . '/');
	}

    #[Route('/tmp/attests/{numero}', name: 'preview_tmp_attest')]
	public function preview_tmp_attest($numero): BinaryFileResponse|Response
    {
		return $this->watch($numero, 0, $this->getParameter("output_tmp_attest") . $this->getUser()->getUsername() . '/');
	}

    #[Route('/releves/{numero}/{index}', name: 'preview_rn')]
	public function preview_rn($numero, $index): BinaryFileResponse|Response
    {
		return $this->watch($numero, $index, $this->getParameter("output_dir_rn"));
	}

    #[Route('/attests/{numero}/{index}', name: 'preview_attest')]
	public function preview_attest($numero, $index): BinaryFileResponse|Response
    {
		return $this->watch($numero, $index, $this->getParameter("output_dir_attest"));
	}

	/**
	 * @param int $numero
	 * @param int $index
	 * @param string $directory
	 * @return BinaryFileResponse|Response
	 */
	private function watch(int $numero, int $index, string $directory): BinaryFileResponse|Response
    {
		return PdfResponse::getPdfResponse($index, $directory . $numero, false);
	}

}