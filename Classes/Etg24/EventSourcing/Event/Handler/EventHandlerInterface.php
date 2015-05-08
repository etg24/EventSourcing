<?php
namespace Etg24\EventSourcing\Event\Handler;

use Etg24\EventSourcing\Event\DomainEvent;

interface EventHandlerInterface {

	/**
	 * @param DomainEvent $event
	 * @return boolean
	 */
	public function canHandleEvent(DomainEvent $event);

	/**
	 * @param DomainEvent $event
	 * @return void
	 */
	public function handle(DomainEvent $event);

}