<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Form\ImportType;
use App\Logic\CustomFinder;
use App\Parser\IEtuParser;
use App\Repository\HistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/monitoring")
 */
class MonitoringController extends AbstractController
{
	/**
	 * @Route("/rn", name="monitoring_rn")
	 * @param HistoryRepository $repo
	 * @return Response
	 */
	public function monitoring_rn(HistoryRepository $repo): Response
	{
		$admins = $this->getParameter('admin_users');
		$isAdmin = in_array($this->getUser()->getUsername(), $admins);

		if ($isAdmin)
			$histories = $repo->findRNHistories();
		else
			$histories = $repo->findRNHistoriesForUser($this->getUser()->getUsername());

		return $this->render("monitoring/monitoring.html.twig", [
			'histories' => $histories,
			'mode' => ImportedData::RN,
			'isAdmin' => $isAdmin
		]);
	}

	/**
	 * @Route("/attest", name="monitoring_attest")
	 * @param HistoryRepository $repo
	 * @return Response
	 */
	public function monitoring_attest(HistoryRepository $repo): Response
	{
		$admins = $this->getParameter('admin_users');
		$isAdmin = in_array($this->getUser()->getUsername(), $admins);

		if ($isAdmin)
			$imports = $repo->findAttestHistories();
		else
			$imports = $repo->findAttestHistoriesForUser($this->getUser()->getUsername());

		return $this->render("monitoring/monitoring.html.twig", [
			'histories' => $imports,
			'mode' => ImportedData::ATTEST,
			'isAdmin' => $isAdmin
		]);
	}

	/**
	 * @Route("/import/rn", name="delete_import_rn")
	 * @param Request $request
	 * @param IEtuParser $parser
	 * @return Response
	 */
	function delete_import_rn(Request $request, IEtuParser $parser): Response
	{
		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::DELETE, "type" => ImportType::RELEVE]);

		return $this->delete_import($request, $parser, $form, $this->getParameter("output_dir_rn"), ImportedData::RN);
	}

	/**
	 * @Route("/import/attest", name="delete_import_attest")
	 * @param Request $request
	 * @param IEtuParser $parser
	 * @return Response
	 */
	function delete_import_attest(Request $request, IEtuParser $parser): Response
	{
		$form = $this->createForm(ImportType::class, null, ["act" => ImportType::DELETE, "type" => ImportType::ATTEST]);

		return $this->delete_import($request, $parser, $form, $this->getParameter("output_dir_attest"), ImportedData::ATTEST);
	}

	private function delete_import(Request $request, IEtuParser $parser, FormInterface $form, string $dir, int $mode): Response
	{
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$etu = $parser->parseETU($data->getEtu());
			$finder = new CustomFinder();

			$admin = in_array("ROLE_ADMIN", $this->getUser()->getRoles());

			$repo = $this->getDoctrine()->getManager()->getRepository(ImportedData::class);
			$bddData = $mode == ImportedData::RN ? $repo->findRn($data, $this->getUser()->getUsername(), $admin) : $repo->findAttest($data, $this->getUser()->getUsername(), $admin);

			$error = true;
			if ($bddData != null) {
				foreach ($etu as $stud) {
					$year = $data->getYear() . '-' . (substr($data->getYear(), 2, 2) + 1);
					$filename = $mode == ImportedData::RN ? $parser->getReleveFileName($year, $stud, $bddData) : $parser->getAttestFileName($year, $stud, $bddData);
					if (is_file($dir . $stud->getNumero() . '/' . $filename)) {
						$error = false;
						$stud->setFile($filename);
						$stud->setIndex($finder->getFileIndex($dir . $stud->getNumero(), $stud->getFile()));
					}
				}
			}
			if (!$error)
				return $this->render('releve_notes/selection_delete.html.twig', ['students' => $etu, 'parser' => $parser, 'mode' => $mode, 'bddData' => $bddData]);
		}

		return $this->render("monitoring/delete_import.html.twig", [
			'form' => $form->createView(),
			'mode' => $mode,
			'error' => isset($error) && $error ? 'Aucun document ne correspond aux informations.' : null
		]);
	}

	/**
	 * @Route("/delete/rn", name="delete_rn")
	 * @param Request $request
	 * @return JsonResponse
	 */
	function delete_rn(Request $request): JsonResponse
	{
		return $this->delete($request, $this->getParameter('output_dir_rn'));
	}

	/**
	 * @Route("/delete/attest", name="delete_attest")
	 * @param Request $request
	 * @return JsonResponse
	 */
	function delete_attest(Request $request): JsonResponse
	{
		return $this->delete($request, $this->getParameter('output_dir_attest'));
	}

	private function delete(Request $request, string $dir): JsonResponse
	{
		$ids = $request->get("ids");
		$dataId = $request->get("dataId");

		// Ajax call may be fail if ids is to big.
		// max_input_vars has been up to 3500 in php.ini
		if ($ids == null || $dataId == null)
			return new JsonResponse(null, 500);

		$em = $this->getDoctrine()->getManager();

		$bddData = $em->getRepository(ImportedData::class)->find($dataId);

		$last_hist = $bddData->getLastHistory();

		$hist = new History($last_hist->getNbFiles() - count($ids), History::Modified);
		$bddData->addHistory($hist);

		foreach ($ids as $id) {
			$file = $dir . $id['id'] . '/' . $id['file'];
			if (is_file($file))
				unlink($file);
		}

		$em->persist($bddData);
		$em->flush();

		return new JsonResponse();
	}
}