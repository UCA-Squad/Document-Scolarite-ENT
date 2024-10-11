<?php


namespace App\Listener;


use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceListener
{
    private bool $isMaintenance;

    public function __construct(ParameterBagInterface $params, private Environment $twig, private Security $security)
    {
        $this->isMaintenance = $params->has('is_maintenance') ? $params->get('is_maintenance') : false;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if ($this->isMaintenance === true && !$isAdmin) {

            $content = $this->twig->render('closed.html.twig');
            $event->setResponse(new Response($content, 200));
            $event->stopPropagation();
        }
    }
}