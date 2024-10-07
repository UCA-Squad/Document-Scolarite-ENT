<?php

namespace App\Controller;

use App\Security\AesCipher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PublicController extends AbstractController
{
    #[Route('/{route}', name: 'scola', requirements: ['route' => '^(?!api|_(profiler|wdt)).*']), IsGranted('ROLE_ETUDIANT')]
    public function scolaIndex(AesCipher $aes): Response
    {
        $b64Info = json_encode([
            'username' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles(),
            'encryptedUsername' => $aes->encrypt($this->getUser()->getUserIdentifier()),
            'numero' => $this->getUser()->getNumero(),
        ]);

        return $this->render('base.html.twig', [
            'b64Info' => base64_encode($b64Info),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }
}
