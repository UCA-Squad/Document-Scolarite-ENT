<?php


namespace App\Security;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Request $request, AccessDeniedException $accessDeniedException)
	{
		return new Response($this->container->get('twig')->render('public/access_denied.html.twig'));
	}
}