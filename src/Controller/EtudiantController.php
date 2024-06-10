<?php

namespace App\Controller;

use App\Logic\CustomFinder;
use App\Logic\LDAP;
use App\Logic\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/{numero}', name: 'api_student')]
    public function api_etudiant(int $numero, ParameterBagInterface $params, LDAP $ldap): JsonResponse
    {
        if (!$this->isGranted("ROLE_SCOLA") && $numero != $this->getUser()->getNumero()) {
            return new JsonResponse("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);
        }

        $dir_rn = $params->get("output_dir_rn") . $numero;
        $dir_attest = $params->get("output_dir_attest") . $numero;
        $finder = new CustomFinder();

        $rns = $finder->getFiles($dir_rn);
        $attests = $finder->getFiles($dir_attest);

        $jsonRns = [];
        $jsonAttests = [];

        $data = [];

        $i = 0;
        foreach ($rns as $rn) {
            $year = explode("_", $rn->getFilename())[1];
//            $jsonRns[$year][] = [
//                'name' => $rn->getFilename(),
//                'date' => date("d/m/Y", $rn->getCTime()),
//                'index' => $i++,
//            ];
            $data[$year]['rn'][] = [
                'name' => $rn->getFilename(),
                'index' => $i++,
            ];
        }

        $i = 0;
        foreach ($attests as $attest) {
            $year = explode("_", $attest->getFilename())[1];
//            $jsonAttests[$year][] = [
//                'name' => $attest->getFilename(),
//                'date' => date("d/m/Y", $attest->getCTime()),
//                'index' => $i++,
//            ];
            $data[$year]['attest'][] = [
                'name' => $attest->getFilename(),
                'index' => $i++,
            ];
        }

        $ldapUser = current($ldap->search("(CLFDcodeEtu=$numero)", "ou=people,", ["CLFDcodeEtu", "sn", "givenName"]));
        $user = [
            'numero' => $numero,
            'nom' => $ldapUser->getAttribute("sn")[0],
            'prenom' => $ldapUser->getAttribute("givenName")[0],
        ];

//        dd($data);

        return new JsonResponse([
            'rns' => $jsonRns,
            'attests' => $jsonAttests,
            'student' => $user,
            'data' => $data,
        ]);
    }

    #[Route('/download/releve/{numero}/{index}', name: 'download_rn')]
    public function download_rn(int $numero, int $index): Response
    {
        if (!$this->isGranted("ROLE_SCOLA")) {
            if ($numero != $this->getUser()->getNumero())
                return new Response("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);
        }


        $directory = $this->getParameter("output_dir_rn");

        return PdfResponse::getPdfResponse($index, $directory . $numero, true);
    }

    #[Route('/download/attest/{numero}/{index}', name: 'download_attest')]
    public function download_attest(int $numero, int $index): Response
    {
        if (!$this->isGranted("ROLE_SCOLA")) {
            if ($numero != $this->getUser()->getNumero())
                return new Response("Vous n'avez pas les autorisations nécessaires pour afficher ce contenu", 403);
        }

        $directory = $this->getParameter("output_dir_attest");
        return PdfResponse::getPdfResponse($index, $directory . $numero, true);
    }
}
