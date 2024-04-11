<?php


namespace App\Controller;


use App\Entity\History;
use App\Logic\FileAccess;
use App\Parser\EtuParser;
use App\Repository\HistoryRepository;
use App\Repository\ImportedDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/monitoring')]
class MonitoringController extends AbstractController
{
    #[Route('/rn', name: 'get_monitoring_rn')]
    public function get_monitoring_rn(ImportedDataRepository $importedDataRepository): JsonResponse
    {
        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
            $imports = $importedDataRepository->findAllRns();
        else
            $imports = $importedDataRepository->findAllRns($this->getUser()->getUserIdentifier());

        usort($imports, function ($a, $b) {
            return $a->getLastHistory()->getDate() < $b->getLastHistory()->getDate();
        });

        return new JsonResponse($imports);
    }

    #[Route('/attest', name: 'get_monitoring_attest')]
    public function monitoring_attest(ImportedDataRepository $importedDataRepository): Response
    {
        if (in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
            $imports = $importedDataRepository->findAllAttests();
        else
            $imports = $importedDataRepository->findAllAttests($this->getUser()->getUserIdentifier());

        usort($imports, function ($a, $b) {
            return $a->getLastHistory()->getDate() < $b->getLastHistory()->getDate();
        });

        return new JsonResponse($imports);
    }

    #[Route('/delete', name: 'api_delete_file', methods: ['POST'])]
    public function removeFile(Request $request, ImportedDataRepository $repo, FileAccess $fileAccess, EtuParser $parser, EntityManagerInterface $em): JsonResponse
    {
        $params = json_decode($request->getContent(), true);

        $dataId = $params['dataId'];
        $numsEtu = $params['numsEtu'];

        $data = $repo->find($dataId);
        if (!isset($data) || empty($numsEtu))
            return $this->json('Missing params', 403);

        if ($data->isRn())
            $folder = $fileAccess->getRn();
        else
            $folder = $fileAccess->getAttest();


        $data->addHistory(new History($data->getHistory()->last()->getNbFiles(), History::Modified));

        foreach ($numsEtu as $numEtu) {

            if ($data->isRn())
                $filename = $parser->getReleveFileName($data, $numEtu);
            else
                $filename = $parser->getAttestFileName($data, $numEtu);

            if (!file_exists($folder . $numEtu . "/" . $filename))
                return $this->json('Impossible de supprimer le document', 500);

            $data->getHistory()->last()->setNbFiles($data->getHistory()->last()->getNbFiles() - 1);
            unlink($folder . $numEtu . "/" . $filename);
        }

        $em->persist($data);
        $em->flush();

        return $this->json(null);
    }
}