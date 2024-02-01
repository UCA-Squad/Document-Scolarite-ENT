<?php

namespace App\Controller;

use App\Security\AesCipher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
//    /**
//     * Route principale.
//     * Redirige les étudiants vers leurs documents.
//     * Redirige les autres utilisateurs (gestionnaires / admin) sur l'écran de recherche.
//     * @Route("/", name="home")
//     */
//    public function index(): RedirectResponse
//    {
//        if (in_array("ROLE_ETUDIANT", $this->getUser()->getRoles()))
//            return $this->redirectToRoute("etudiant_home");
//        else
//            return $this->redirectToRoute("student_search");
//    }

    /**
     * Redirige sur les actions possibles pour les gestionnaires.
     * @Route("/{route}", name="scola", requirements={"route"="^(?!api|selection|transfert|_(profiler|wdt)).*"})
     * @IsGranted("ROLE_ETUDIANT")
     */
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

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {

    }
}
