<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\PdfResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/selection")
 * @IsGranted("ROLE_SCOLA")
 */
class SelectionController extends AbstractController
{
    private $file_access;
    private $finder;

    public function __construct(FileAccess $file_access, CustomFinder $finder)
    {
        $this->file_access = $file_access;
        $this->finder = $finder;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/rn", name="api_selection_rn")
     */
    public function api_get_selection_rn(Request $request): JsonResponse
    {
        $bddData = $request->getSession()->get('data');
        $etu = $request->getSession()->get('students');

        foreach ($etu as $entry) {
            $entry->LoadFile($this->file_access->getTmpByMode(ImportedData::RN), $this->file_access->getDirByMode(ImportedData::RN));
        }

        return new JsonResponse(['data' => $bddData, 'students' => $etu,]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/attest", name="api_selection_attest")
     */
    public function api_get_selection_attest(Request $request): JsonResponse
    {
        $bddData = $request->getSession()->get('data');
        $etu = $request->getSession()->get('students');

        foreach ($etu as $entry) {
            $entry->LoadFile($this->file_access->getTmpByMode(ImportedData::ATTEST), $this->file_access->getDirByMode(ImportedData::ATTEST));
        }

        return new JsonResponse(['data' => $bddData, 'students' => $etu,]);
    }

    /**
     * Reconstruit un document PDF avec les PDFs qui ont été transférés dans les dossiers étudiants.
     * @Route("/rebuild", name="rebuild_doc")
     */
    public function reBuild(Request $request): JsonResponse
    {
        $mode = $request->get('mode');
        $folder = $this->file_access->getTmpByMode($mode);
        $new_path = $folder . 'rebuild.pdf';

        $etu = $request->getSession()->get('students');
        $transfered = $this->getEtuTransfered($request->getSession()->get('transfered'), $etu);

        // Trie des étudiants par nom,prenom
        usort($transfered, function ($a, $b) {
            $cmpNom = strcmp($a[0]->getName(), $b[0]->getName());
            $cmpPrenom = strcmp($a[0]->getSurname(), $b[0]->getSurname());
            return $cmpNom == 0 ? $cmpPrenom : $cmpNom;
        });

        $cmd = "gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' ";
        foreach ($transfered as $key => $transfer) {
            $filepath = str_replace(' ', "\ ", $transfer[1]);
            $filepath = str_replace('(', "\(", $filepath);
            $filepath = str_replace(')', "\)", $filepath);
            $cmd .= $filepath . " ";
        }

        try {
            $proc = Process::fromShellCommandline($cmd);
            $proc->setTimeout(null);
            $proc->setIdleTimeout(null);
            $proc->run();

            $index = $this->finder->getFileIndex($folder, "rebuild.pdf");
            return new JsonResponse(['index' => $index], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['index' => -1], 500);
        }
    }

    /**
     * Map les documents transférés à l'étudiant correspondant.
     * @param array $transfered
     * @param array $studs
     * @return array
     */
    private function getEtuTransfered(array $transfered, array $studs): array
    {
        $res = [];
        foreach ($studs as $stud) {
            foreach ($transfered as $transfert) {
                if (str_contains($transfert, $stud->getNumero())) {
                    $res[$stud->getNumero()] = [$stud, $transfert];
                    break;
                }
            }
        }
        return $res;
    }

    /**
     * Retourne le document pdf rebuild sous forme de réponse PDF.
     * @Route("/rebuild/{mode}", name="get_rebuilded_doc")
     * @param int $mode
     * @return BinaryFileResponse|Response
     */
    public function get_rebuilded_doc(int $mode)
    {
        $folder = $this->file_access->getTmpByMode($mode);
        $index = $this->finder->getFileIndex($folder, "rebuild.pdf");

        return PdfResponse::getPdfResponse($index, $folder);
    }
}