<?php

namespace App\Controller;

use App\Logic\CustomFinder;
use App\Logic\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/etudiant")
 */
class EtudiantController extends AbstractController
{
	/**
	 * @Route("/{numero}", name="etudiant_home", requirements={"numero"="\d+"})
	 * @param int|null $numero
	 * @return Response
	 */
	public function etudiant(int $numero = null): Response
	{
		if (!is_null($numero) && !$this->isGranted("ROLE_SCOLA"))
			return new Response("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);

		$dir_rn = $this->getParameter("output_dir_rn") . (is_null($numero) ? $this->getUser()->getExtraFields()["numero"] : $numero);
		$dir_attest = $this->getParameter("output_dir_attest") . (is_null($numero) ? $this->getUser()->getExtraFields()["numero"] : $numero);
		$finder = new CustomFinder();

		$rns = $finder->getFilesName($dir_rn);
		$attests = $finder->getFilesName($dir_attest);

		return $this->render('public/etudiant.html.twig', [
			'rns' => $rns,
			'attests' => $attests,
			'numero' => is_null($numero) ? $this->getUser()->getExtraFields()["numero"] : $numero,
			'is_scola' => in_array('ROLE_SCOLA', $this->getUser()->getRoles()) || in_array('ROLE_ADMIN', $this->getUser()->getRoles())
		]);
	}

	/**
	 * @Route("/download/releve/{numero}/{index}", name="download_rn")
	 * @param $numero
	 * @param $index
	 * @return BinaryFileResponse|Response
	 */
	public function download_rn(int $numero, $index)
	{
		if (!$this->isGranted("ROLE_SCOLA")) {
			if ($numero != $this->getUser()->getExtraFields()["numero"])
				return new Response("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);
		}

		$directory = $this->getParameter("output_dir_rn");
		return PdfResponse::getPdfResponse($index, $directory . $numero, true);
	}

	/**
	 * @Route("/download/attest/{numero}/{index}", name="download_attest")
	 * @param int $numero
	 * @param $index
	 * @return BinaryFileResponse|Response
	 */
	public function download_attest(int $numero, $index)
	{
		if (!$this->isGranted("ROLE_SCOLA")) {
			if ($numero != $this->getUser()->getExtraFields()["numero"])
				return new Response("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);
		}

		$directory = $this->getParameter("output_dir_attest");
		return PdfResponse::getPdfResponse($index, $directory . $numero, true);
	}
}
