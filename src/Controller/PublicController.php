<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
	/**
	 * @Route("/", name="home")
	 */
	public function index(): RedirectResponse
	{
		if (in_array("ROLE_ETUDIANT", $this->getUser()->getRoles()))
			return $this->redirectToRoute("etudiant_home");
		else
			return $this->redirectToRoute("student_search");
	}

	/**
	 * @Route("/scola", name="scola")
	 * @IsGranted("ROLE_SCOLA")
	 */
	public function scolaIndex(): Response
	{
		return $this->render('public/home.html.twig');
	}

	/**
	 * @Route("/logout", name="logout")
	 */
	public function logout()
	{

	}
}
