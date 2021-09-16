<?php


namespace App\Listener;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceListener
{
	private $params;
	private $twig;

	public function __construct(ParameterBagInterface $params, Environment $twig)
	{
		$this->params = $params;
		$this->twig = $twig;
	}

	public function onKernelRequest(RequestEvent $event)
	{
		$isMaintenance = $this->params->has('is_maintenance') ? $this->params->get('is_maintenance') : false;

		if ($isMaintenance == true) {
			$content = $this->twig->render('closed.html.twig');
			$event->setResponse(new Response($content, 200));
			$event->stopPropagation();
		}
	}
}