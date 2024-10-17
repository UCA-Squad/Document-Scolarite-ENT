<?php


namespace App\Security;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private Environment           $twig, private Security $security, private AesCipher $aes,
                                private ParameterBagInterface $params, private LoggerInterface $logger)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $url_login = $this->params->get('menu_url_login');
        $url_logout = $this->params->get('menu_url_logout');

        $b64Info = json_encode([
            'username' => $this->security->getUser()->getUserIdentifier(),
            'roles' => $this->security->getUser()->getRoles(),
            'encryptedUsername' => $this->aes->encrypt($this->security->getUser()->getUserIdentifier()),
            'numero' => $this->security->getUser()->getNumero(),
            'url_login' => $url_login,
            'url_logout' => $url_logout,
        ]);

        $this->logger->info('Access denied for user ' . $this->security->getUser()->getUserIdentifier());

        return new Response($this->twig->render('public/access_denied.html.twig', [
            'b64Info' => base64_encode($b64Info),
        ]));
    }
}