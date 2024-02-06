<?php


namespace App\Controller;


use App\Entity\History;
use App\Entity\ImportedData;
use App\Exception\ImportException;
use App\Logic\FileAccess;
use App\Logic\PDF;
use App\Parser\EtuParser;
use App\Parser\IEtuParser;
use App\Repository\ImportedDataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/import")
 * @IsGranted("ROLE_SCOLA")
 */
class ImportController extends AbstractController
{
    private $file_access;
    private $parser;
    private $session;

    private $repo;

    private $pdfTool;

    public function __construct(FileAccess             $file_access, IEtuParser $parser, RequestStack $session,
                                ImportedDataRepository $repo, PDF $pdfTool)
    {
        $this->file_access = $file_access;
        $this->parser = $parser;
        $this->session = $session->getSession();
        $this->repo = $repo;
        $this->pdfTool = $pdfTool;
    }

    /**
     * @param ImportedData $import
     * @param FileAccess $fileAccess
     * @param EtuParser $parser
     * @return JsonResponse
     * @Route("/imported/{id}")
     */
    public function getImportedFiles(ImportedData $import, FileAccess $fileAccess, EtuParser $parser): JsonResponse
    {
        if (empty($import->getSemestre()) && empty($import->getSession())) {
            $folder = $fileAccess->getAttest();
            $pattern = "*/" . $parser->getAttestFileName($import, '*');
        } else {
            $folder = $fileAccess->getRn();
            $pattern = "*/" . $parser->getReleveFileName($import, '*');
        }

        $files = glob($folder . $pattern);
        foreach ($files as &$file) $file = basename($file);
        return $this->json($files);
    }

    /**
     * @Route("/rn", name="api_import_rn", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function api_import_rn(Request $request): Response
    {
        return $this->import_generique($request, ImportedData::RN);
    }

    /**
     * @Route("/attests", name="api_import_attests", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function import_attests(Request $request): Response
    {
        return $this->import_generique($request, ImportedData::ATTEST);
    }

    /**
     * @Route("/truncate_unit", name="truncate_by_unit")
     */
    public function truncateByUnit(Request $request, PDF $pdfTool, ImportedDataRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mode = $data['mode'];
        $page = $data['page'];

        if (!isset($mode) || !isset($page))
            return new JsonResponse("Paramètre incomplet", 404);

        $tampon_position = $request->getSession()->get('tampon');
        if (isset($tampon_position)) {
            $pdfTool->setupPosition($tampon_position['x'], $tampon_position['y']);
            $pdfTool->setupImage($this->file_access->getTamponByMode($mode));
        }

        if ($request->getSession()->get('data') !== null)
            $importedData = $request->getSession()->get('data');
        else {
            // Should be initiated from import
            dd("Should be initiated from import");
            //$importedData = $repo->findLastDataByMode($mode, $username);
            //$request->getSession()->set('data', $importedData);
        }

        $mode == ImportedData::RN ? $pdfTool->setupRn() : $pdfTool->setupAttest();

        $tmp_folder = $this->file_access->getTmpByMode($mode);
        $indexes = $request->getSession()->get('indexes');
        $etu = $request->getSession()->get('students');


        $count = 0;
        $batchCount = 25;
        $page = $pdfTool->truncateFileByPage($this->file_access->getPdfByMode($mode), $importedData, $tmp_folder, $indexes, $etu, $page);
        while ($page > 0 && $count < $batchCount) {
            $page = $pdfTool->truncateFileByPage($this->file_access->getPdfByMode($mode), $importedData, $tmp_folder, $indexes, $etu, $page);
            $count++;
        }

        return new JsonResponse($page);
    }

    private function import_generique(Request $request, int $mode): JsonResponse
    {
        $pdfFile = $request->files->get('pdf');
        $etuFile = $request->files->get('etu');

        $tampon = $request->files->get('tampon');
        $numTampon = $request->get('numTampon');

        $import = (new ImportedData())
            ->setPdfFilename($pdfFile->getClientOriginalName())
            ->setEtuFilename($etuFile->getClientOriginalName())
            ->setSession($request->get('sess'))
            ->setLibelleForm($request->get('lib'))
            ->setSemestre($request->get('sem'));

        // existing import with same files name
        $existingImport = $this->repo->findOneBy([
            'pdf_filename' => $import->getPdfFilename(),
            'etu_filename' => $import->getEtuFilename()
        ]);

        // if the existing import params match
        $sameParams = isset($existingImport) &&
            $existingImport->getSemestre() == $import->getSemestre() &&
            $existingImport->getSession() == $import->getSession();

//        dd($existingImport, $import);

        if (isset($existingImport)) {
            $nbFiles = $existingImport->getHistory()->last()->getNbFiles();
            $existingImport->addHistory(new History($nbFiles));
        } else {
            $import->addHistory(new History(0));
        }

        // Throw an error if an import with same files and different params exists
        if (isset($existingImport) && !$sameParams) {
            return $this->json(['error' => "L'import existe déjà"], 500);
        }

        $this->session->clear();


        $mode == ImportedData::RN ? $this->pdfTool->setupRn() : $this->pdfTool->setupAttest();
        $shouldTampon = $this->import($mode, $existingImport ?? $import, $pdfFile, $etuFile, $tampon, $numTampon);


        $pageCount = $this->pdfTool->getPageCount($this->file_access->getPdfByMode($mode));
        $pageFirst = $request->getSession()->get('indexes') !== null ? array_key_first($request->getSession()->get('indexes')) : null;

        return $this->json([
            'step' => $shouldTampon ? 'tampon' : 'truncate',
            'mode' => $mode,
            'pageCount' => $pageCount,
            'pageFirst' => $pageFirst,
            'sameFiles' => isset($existingImport),
            'sameParams' => $sameParams,
        ]);
    }

    /**
     * @param int $mode
     * @param ImportedData $data
     * @param UploadedFile $pdfFile
     * @param UploadedFile $etuFile
     * @param UploadedFile|null $tampon
     * @param int $numTampon
     * @return bool
     * @throws ImportException
     * @throws PdfParserException
     * @throws PdfReaderException
     * @throws CrossReferenceException
     * @throws FilterException
     * @throws PdfTypeException
     * Retourne true si on doit continuer avec le tamponnage
     */
    private function import(int $mode, ImportedData $data, UploadedFile $pdfFile, UploadedFile $etuFile, UploadedFile $tampon = null, int $numTampon = 0): bool
    {
        $this->import_process($data, $mode, $pdfFile, $etuFile, $tampon);

        if (isset($tampon) && $numTampon > 0) {
            $solo_index = $this->extractFirstIndex($this->session->get('indexes'), $numTampon);
            $this->pdfTool->truncateFile($this->file_access->getPdfByMode($mode), $data,
                $this->getUser()->getUsername() . '/', $solo_index, $this->session->get('students'),
                true);
            return true;
        }

        return false;
    }

    /**
     * Traitement commun : initialise les données.
     * @param ImportedData $data
     * @param int $mode
     * @param UploadedFile $pdfFile
     * @param UploadedFile $etuFile
     * @param UploadedFile|null $tampon_img
     * @return void
     * @throws ImportException
     */
    private function import_process(ImportedData $data, int $mode, UploadedFile $pdfFile, UploadedFile $etuFile, UploadedFile $tampon_img = null)
    {
        // Rewrite the pdf file with GhostScript to use it with pdf lib
        if (!$this->rewritePdf($pdfFile, $mode))
            throw new ImportException("L'application n'a pas réussi à convertir le fichier " . $pdfFile->getClientOriginalName());

        // Parse the file into Student array with the defined normalizer service
        $etu = $this->parser->parseETU($etuFile);
        $this->session->set('students', $etu);
        // Move the file into the defined location
        $etuFile->move($this->file_access->getEtuByMode($mode, 'd'), $this->file_access->getEtuByMode($mode, 'f'));

        // Images
        if (isset($tampon_img)) {
            $tampon_img->move($this->file_access->getTamponByMode($mode, 'd'), $this->file_access->getTamponByMode($mode, 'f'));
        }

        // Index process to handle pagination
        [$date, $indexes] = $this->pdfTool->indexPages($this->file_access->getPdfByMode($mode), $etu);
        $this->session->set('indexes', $indexes);

        if ($indexes === false) {
            unlink($this->file_access->getEtuByMode($mode));
            throw new ImportException("L'application n'a pas réussi à extraire les informations d'un ou plusieurs étudiant(s)");
        }

        $data->LoadStudentData($etu[0], $date, count($etu), $this->getUser()->getUsername());

        $this->session->set('data', $data);
    }


    /**
     * Extrait un index
     * @param array $indexes
     * @param int $num_page
     * @return array
     */
    private function extractFirstIndex(array $indexes, int $num_page): array
    {
        $max = count($indexes);
        $max += array_key_first($indexes) - 1;

        if ($num_page > $max)
            $num_page = $max;

        // Si la numérotation commence apres num_page
        if ($num_page < array_key_first($indexes))
            $num_page = array_key_first($indexes);

        return [$num_page => $indexes[$num_page]];
    }

    /**
     * Réécris le document PDF avec la librairie GhostScript si impossible de le lire.
     * @param UploadedFile $pdf Le document PDF.
     * @param int $mode
     * @return bool Succès ou Échec.
     */
    private function rewritePdf(UploadedFile $pdf, int $mode): bool
    {
        $new_path = $this->file_access->getPdfByMode($mode, 'd');
        $name = $this->file_access->getPdfByMode($mode, 'f');

//        try {
//            $pageCount = $this->pdfTool->getPageCount($pdf->getPathname()); // Trigger une exeception si le format du pdf est illisible
//            $pdf->move($new_path, $name);
//
//        } catch (PdfParserException|CrossReferenceException $e) {

        $cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='" . $new_path . $name . "' '" . $pdf->getPathname() . "'";
        try {
            Process::fromShellCommandline($cmd)->setTimeout(null)->setIdleTimeout(null)->run();
        } catch (\Exception $e) {
            return false;
        }
//        }

        return true;
    }

}