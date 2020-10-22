<?php

namespace App\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class Events
{
	/**
	 * @Event("Symfony\Component\EventDispatcher\GenericEvent")
	 */
	const STUDENT_TRANSFERED = "student.transfered";

	/**
	 * @Event("Symfony\Component\EventDispatcher\GenericEvent")
	 */
	const STUDENT_DELETED = "student.deleted";
}