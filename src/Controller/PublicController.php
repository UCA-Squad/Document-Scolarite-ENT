<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
	/**
	 * @Route("/", name="home")
	 */
	public function index()
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
	public function scolaIndex()
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
