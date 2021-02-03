<?php


namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_SCOLA")
 */
class ScolaController extends AbstractController
{

	/**
	 * @Route("/search", name="student_search")
	 * @param Request $request
	 * @return RedirectResponse|Response
	 */
	public function search(Request $request)
	{
		$student_form = $this->createFormBuilder()
			->add('num', TextType::class, ['label' => 'Numéro étudiant', 'attr' => ['pattern' => "\d+"]])
			->add('submit', SubmitType::class, ['label' => 'Rechercher'])
			->getForm();

		$student_form->handleRequest($request);

		if ($student_form->isSubmitted() && $student_form->isValid()) {
			$num = $student_form->get('num')->getData();
			return $this->redirectToRoute('etudiant_home', ['numero' => $num]);
		}
		return $this->render('etudiant/search.html.twig', [
			'form' => $student_form->createView(),
		]);
	}

}