<?php


namespace App\Logic;


use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PdfResponse
{
	public static function getPdfResponse($index, $directory, $download = true)
	{
		$finder = new CustomFinder();
		$documents = $finder->getFilesName($directory);

		if (!isset($documents[$index]))
			return new Response("Document non disponible", 404);

		$response = new BinaryFileResponse($directory . "/" . $documents[$index]);
		$response->setContentDisposition($download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE, $documents[$index]);
		return $response;
	}
}