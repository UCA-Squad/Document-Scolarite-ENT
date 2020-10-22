<?php


namespace App\Listener;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MaintenanceListener
{
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function onKernelRequest(RequestEvent $event)
	{
		$isMaintenance = $this->container->hasParameter('is_maintenance') ? $this->container->getParameter('is_maintenance') : false;

		if ($isMaintenance === true) {
			$twig_engine = $this->container->get("twig");
			$content = $twig_engine->render('closed.html.twig');
			$event->setResponse(new Response($content, 200));
			$event->stopPropagation();
		}
	}
}