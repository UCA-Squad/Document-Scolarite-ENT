<?php


namespace App\Controller;


use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\LDAP;
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
	 * @param LDAP $ldap
	 * @return RedirectResponse|Response
	 */
	public function search(Request $request, LDAP $ldap, FileAccess $fileAccess, CustomFinder $finder)
	{
		$student_form = $this->get("form.factory")->createNamedBuilder("form_by_num")
			->add('num', TextType::class, ['label' => 'Numéro étudiant', 'attr' => ['pattern' => "\d+"]])
			->add('submit', SubmitType::class, ['label' => 'Rechercher'])
			->getForm();

		$student_form_name = $this->get("form.factory")->createNamedBuilder("form_by_name")
			->add('name', TextType::class, ['label' => 'Nom étudiant', 'attr' => ['' => ""]])
			->add('submit', SubmitType::class, ['label' => 'Rechercher'])
			->getForm();

		$student_form->handleRequest($request);
		$student_form_name->handleRequest($request);

		if ($student_form->isSubmitted() && $student_form->isValid()) {
			$num = $student_form->get('num')->getData();
			$users = $ldap->search("(CLFDcodeEtu=$num)", "ou=people,", ["eduPersonAffiliation", "CLFDcodeEtu", "sn", "givenName", "supannEntiteAffectationPrincipale"]);

			$filtered_users = [];
			foreach ($users as $user) {
				if ($user->hasAttribute("CLFDcodeEtu") && in_array("student", $user->getAttribute("eduPersonAffiliation"))) {
					$num = $user->getAttribute("CLFDcodeEtu")[0];
					$nb_rn = count($finder->getFilesName($fileAccess->getRn() . $num . '/'));
					$nb_attest = count($finder->getFilesName($fileAccess->getAttest() . $num . '/'));
					$user->setAttribute('nb_docs', [$nb_rn + $nb_attest]);
					array_push($filtered_users, $user);
				}
			}
//			return $this->redirectToRoute('etudiant_home', ['numero' => $num]);
		}

		if ($student_form_name->isSubmitted() && $student_form_name->isValid()) {
			$name = $student_form_name->get('name')->getData();
			$users = $ldap->search("(sn=$name)", "ou=people,", ["eduPersonAffiliation", "CLFDcodeEtu", "sn", "givenName", "supannEntiteAffectationPrincipale"]);

			$filtered_users = [];
			foreach ($users as $user) {
				if ($user->hasAttribute("CLFDcodeEtu") && in_array("student", $user->getAttribute("eduPersonAffiliation"))) {
					$num = $user->getAttribute("CLFDcodeEtu")[0];
					$nb_rn = count($finder->getFilesName($fileAccess->getRn() . $num . '/'));
					$nb_attest = count($finder->getFilesName($fileAccess->getAttest() . $num . '/'));
					$user->setAttribute('nb_docs', [$nb_rn + $nb_attest]);
					array_push($filtered_users, $user);
				}
			}
		}

		if (isset($filtered_users))
			dump($filtered_users);

		return $this->render('etudiant/search.html.twig', [
			'form_by_num' => $student_form->createView(),
			'form_by_name' => $student_form_name->createView(),
			'users' => isset($filtered_users) ? $filtered_users : null
		]);
	}

	private function getNbDocs(string $folder, CustomFinder $finder)
	{
		return count($finder->getFilesName($folder));
	}

}