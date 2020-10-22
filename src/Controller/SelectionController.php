<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/selection")
 * @IsGranted("ROLE_SCOLA")
 */
class SelectionController extends AbstractController
{
	/**
	 * @Route("/releves", name="selection_rn")
	 * @Cache(vary={"no-cache", "must-revalidate", "no-store"})
	 * @param IEtuParser $parser
	 * @return RedirectResponse|Response
	 */
	public function selection_rn(IEtuParser $parser, ImportedDataRepository $repo)
	{
		$directory = $this->getParameter("output_tmp_rn") . $this->getUser()->getUsername() . '/';
		$final_dir = $this->getParameter("output_dir_rn");
		$etu_dir = $this->getParameter("output_etu_rn");

		$etu = $this->selection($directory, $etu_dir, $final_dir, $parser);

		$bddData = $repo->findLastRnData($this->getUser()->getUsername());

		if (empty($etu))
			return $this->redirectToRoute('import_rn');

		return $this->render('releve_notes/selection.html.twig', ['students' => $etu, 'bddData' => $bddData, 'mode' => ImportedData::RN]);
	}

	/**
	 * @Route("/attests", name="selection_attests")
	 * @param IEtuParser $parser
	 * @return RedirectResponse|Response
	 */
	public function selection_attests(IEtuParser $parser, ImportedDataRepository $repo)
	{
		$directory = $this->getParameter("output_tmp_attest") . $this->getUser()->getUsername() . '/';
		$final_dir = $this->getParameter("output_dir_attest");
		$etu_dir = $this->getParameter("output_etu_attest");

		$etu = $this->selection($directory, $etu_dir, $final_dir, $parser);

		$bddData = $repo->findLastAttestData($this->getUser()->getUsername());

		if (empty($etu))
			return $this->redirectToRoute('import_attests');

		return $this->render('releve_notes/selection.html.twig', ['students' => $etu, 'bddData' => $bddData, 'mode' => ImportedData::ATTEST]);
	}

	private function selection(string $directory, string $etu_dir, string $final_dir, IEtuParser $parser): array
	{
		$finder = new CustomFinder();
		$documents = $finder->getFilesName($directory);

		if (count($documents) == 0 || !file_exists($etu_dir . $this->getUser()->getUsername() . '.etu'))
			return [];

		$etu = $parser->parseETU($etu_dir . $this->getUser()->getUsername() . '.etu');

		foreach ($etu as $entry) {
			$entry->LoadFile($directory, $final_dir);
		}
		return $etu;
	}

	/**
	 * @Route("/cancel/releves", name="cancel_rn")
	 * @param ImportedDataRepository $repo
	 * @return RedirectResponse
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function cancel_rn(ImportedDataRepository $repo)
	{
		$etu = $this->getParameter("output_etu_rn");
		$tmp = $this->getParameter("output_tmp_rn");

		$data = $repo->findLastRnData($this->getUser()->getUsername());

		$this->cancel($etu, $tmp, $data);
		return $this->redirectToRoute('import_rn');
	}

	/**
	 * @Route("/cancel/attests", name="cancel_attest")
	 * @param ImportedDataRepository $repo
	 * @return RedirectResponse
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function cancel_attest(ImportedDataRepository $repo)
	{
		$etu = $this->getParameter("output_etu_attest");
		$tmp = $this->getParameter("output_tmp_attest");

		$data = $repo->findLastAttestData($this->getUser()->getUsername());

		$this->cancel($etu, $tmp, $data);
		return $this->redirectToRoute('import_attests');
	}

	private function cancel(string $etu, string $tmp, ImportedData $data = null)
	{
		$etu .= $this->getUser()->getUsername() . '.etu';
		$tmp .= $this->getUser()->getUsername();

		if (file_exists($etu))
			unlink($etu);

		$finder = new CustomFinder();
		$finder->deleteDirectory($tmp);

		if ($data == null)
			return;

		$em = $this->getDoctrine()->getManager();

		if (count($data->getHistory()) <= 1)    // If count histo == 1 => 1rst import
			$em->remove($data);
		else                                  // else réimport
			$em->remove($data->getLastHistory());
		$em->flush();
	}
}