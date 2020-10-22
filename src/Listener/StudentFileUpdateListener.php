<?php


namespace App\Listener;


use App\Events\Events;
use App\Parser\EtuParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class StudentFileUpdateListener implements EventSubscriberInterface
{
	private $mailer;
	private $parser;
	private $twig;

	public static function getSubscribedEvents(): array
	{
		return [
			Events::STUDENT_TRANSFERED => 'onStudentTransfered',
			Events::STUDENT_DELETED => 'onStudentDeleted',
		];
	}

	public function __construct(MailerInterface $mailer, Environment $twig, EtuParser $parser)
	{
		$this->mailer = $mailer;
		$this->twig = $twig;
		$this->parser = $parser;
	}

	public function onStudentTransfered(GenericEvent $event)
	{
		$numero = $event->getSubject();
		$file_path = $event->getArgument('path');
		$stud = $this->parser->getEtuByNum($numero, $this->parser->parseETU(""));

		/**
		 * DO WHAT YOU WANT
		 */
	}

	public function onStudentDeleted(GenericEvent $event)
	{
		/**
		 * DO WHAT YOU WANT
		 */
	}
}