<?php


namespace App\Controller;


use App\Logic\CustomFinder;
use App\Logic\FileAccess;
use App\Logic\LDAP;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_SCOLA")
 * @Route("/api/scola")
 */
class ScolaController extends AbstractController
{
    private $file_access;
    private $finder;

    public function __construct(FileAccess $fileAccess, CustomFinder $finder)
    {
        $this->file_access = $fileAccess;
        $this->finder = $finder;
    }

    /**
     * @Route("/search", name="api_search")
     */
    public function api_search(Request $request, LDAP $ldap): JsonResponse
    {
        $searchField = $request->getContent();

        $users = $ldap->search("(|(sn=$searchField)(CLFDcodeEtu=$searchField))", "ou=people,",
            ["eduPersonAffiliation", "CLFDcodeEtu", "sn", "givenName", "supannEntiteAffectationPrincipale"]);

        $filtered_users = $this->getFilteredUsers($users);

        return new JsonResponse($filtered_users);
    }

    private function getFilteredUsers(array $users): array
    {
        $filtered_users = [];
        foreach ($users as $user) {
            if ($user->hasAttribute("CLFDcodeEtu") && in_array("student", $user->getAttribute("eduPersonAffiliation"))) {
                $num = $user->getAttribute("CLFDcodeEtu")[0];
                $nb_rn = count($this->finder->getFilesName($this->file_access->getRn() . $num . '/'));
                $nb_attest = count($this->finder->getFilesName($this->file_access->getAttest() . $num . '/'));
                $user->setAttribute('nb_docs', [$nb_rn + $nb_attest]);
                $filtered_users[] = $user->getAttributes();
            }
        }

        return $filtered_users;
    }

}