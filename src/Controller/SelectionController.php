<?php


namespace App\Controller;


use App\Entity\ImportedData;
use App\Logic\FileAccess;
use App\Logic\LDAP;
use App\Parser\IEtuParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/selection'), IsGranted('ROLE_SCOLA')]
class SelectionController extends AbstractController
{
    public function __construct(private FileAccess $file_access)
    {
    }

    #[Route('/rn', name: 'api_selection_rn')]
    public function api_get_selection_rn(Request $request, SerializerInterface $ser): JsonResponse
    {
        $bddData = $request->getSession()->get('data');
        $etu = $request->getSession()->get('students');

        foreach ($etu as $entry) {
            $entry->LoadFile($this->file_access->getTmpByMode(ImportedData::RN), $this->file_access->getDirByMode(ImportedData::RN));
        }

        $json = $ser->serialize(['data' => $bddData, 'students' => $etu], 'json', ['groups' => ['import:read', 'student:read']]);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/attest', name: 'api_selection_attest')]
    public function api_get_selection_attest(Request $request, SerializerInterface $ser): JsonResponse
    {
        $bddData = $request->getSession()->get('data');
        $etu = $request->getSession()->get('students');

        foreach ($etu as $entry) {
            $entry->LoadFile($this->file_access->getTmpByMode(ImportedData::ATTEST), $this->file_access->getDirByMode(ImportedData::ATTEST));
        }

        $json = $ser->serialize(['data' => $bddData, 'students' => $etu], 'json', ['groups' => ['import:read', 'student:read']]);
        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Reconstruit un document PDF avec les PDFs qui ont été transférés dans les dossiers étudiants.
     */
    #[Route('/rebuild/{id}', name: 'rebuild_doc')]
    public function reBuild(ImportedData $import, IEtuParser $parser, LDAP $ldap): JsonResponse
    {
        $mode = $import->isRn() ? 0 : 1;
        $folder = "/tmp";

        if (str_contains($import->getPdfFilename(), '.pdf') === false)
            $fileName = $import->getPdfFilename();
        else
            $fileName = explode('.pdf', $import->getPdfFilename())[0];

        $fileName = $fileName . '_rebuild.pdf';
        $new_path = "$folder/$fileName";

        if ($mode == ImportedData::ATTEST) {
            $folder = $this->file_access->getAttest();
            $pattern = "*/" . $parser->getAttestFileName($import, '*');
        } else {
            $folder = $this->file_access->getRn();
            $pattern = "*/" . $parser->getReleveFileName($import, '*');
        }

        $files = glob($folder . $pattern);
        $filesnames = array_map(fn($file) => basename($file), $files);
        $codesEtu = array_map(fn($file) => explode('_', $file)[0], $filesnames);

        $query = "(|";
        foreach ($codesEtu as $code) {
            $query .= "(CLFDcodeEtu=$code)";
        }
        $query .= ")";
        $ldapUser = $ldap->search($query, "ou=people,", ["CLFDcodeEtu", "sn", "givenName"]);

        $infos = [];

        $i = count($ldapUser);
        if ($i != count($codesEtu))
            return new JsonResponse("Erreur lors de la récupération des informations", 500);

        for ($y = 0; $y < $i; $y++) {

            $ldapNum = $ldapUser[$y]->getAttribute("CLFDcodeEtu")[0];

            $codeIndex = array_search($ldapNum, $codesEtu);
            if ($codeIndex === false)
                return new JsonResponse("Erreur lors de la récupération des informations", 500);

            $infos[] = [
                'codeEtu' => $codesEtu[$codeIndex],
                'nom' => $ldapUser[$y]->getAttribute("sn")[0],
                'prenom' => $ldapUser[$y]->getAttribute("givenName")[0],
                'file' => $filesnames[$codeIndex],
                'filepath' => $files[$codeIndex],
            ];
        }

        // Trie par nom, prénom
        usort($infos, function ($a, $b) {
            return strcasecmp($a['nom'], $b['nom']) ?: strcasecmp($a['prenom'], $b['prenom']);
        });

        $cmd = "gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' ";
        foreach ($infos as $file) {
            $filepath = escapeshellarg($file['filepath']);
            $cmd .= $filepath . " ";
        }

        try {
            $proc = Process::fromShellCommandline($cmd);
            $proc->setTimeout(null);
            $proc->setIdleTimeout(null);
            $proc->run();

            $response = $this->file($new_path, $fileName);
            $response->send();

            if (file_exists($new_path))
                unlink($new_path);

        } catch (\Exception $e) {
            return new JsonResponse("Erreur lors de la reconstruction du document", 500);
        }

        return new JsonResponse("ok");
    }

    #[Route('/rebuild_after_transfert', name: 'rebuild_doc_after_transfert', methods: 'POST')]
    public function reBuildAfterTransfert(Request $request, IEtuParser $parser, FileAccess $fileAccess): JsonResponse
    {
        $import = $request->getSession()->get('data');
        if (!$import)
            return $this->json("Aucune donnée en session", 400);

        $selectedNums = json_decode($request->getContent(), true);
        if (!$selectedNums || !is_array($selectedNums) || count($selectedNums) == 0)
            return $this->json("Aucun étudiant sélectionné", 400);

        $files = [];
        $mode = $import->isRn() ? 0 : 1;
        $folder = $fileAccess->getDirByMode($mode);

        foreach ($selectedNums as $num) {
            if ($mode == ImportedData::ATTEST)
                $file = $num . '/' . $parser->getAttestFileName($import, $num);
            else
                $file = $num . '/' . $parser->getReleveFileName($import, $num);
            $files[] = $folder . $file;
        }

        $genFolder = '/tmp';
        if (str_contains($import->getPdfFilename(), '.pdf') === false)
            $fileName = $import->getPdfFilename();
        else
            $fileName = explode('.pdf', $import->getPdfFilename())[0];

        $fileName = $fileName . '_rebuild.pdf';
        $new_path = "$genFolder/$fileName";

        $cmd = "gs -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile='" . $new_path . "' ";
        foreach ($files as $file) {
            $filepath = escapeshellarg($file);
            $cmd .= $filepath . " ";
        }

        try {
            $proc = Process::fromShellCommandline($cmd);
            $proc->setTimeout(null);
            $proc->setIdleTimeout(null);
            $proc->run();

            $response = $this->file($new_path, $fileName);
            $response->send();

            if (file_exists($new_path))
                unlink($new_path);

        } catch (\Exception $e) {
            return new JsonResponse("Erreur lors de la reconstruction du document", 500);
        }

        return $this->json("ok");
    }
}