<?php
namespace Etg24\EventSourcing\Event\Bus;

use Etg24\EventSourcing\Event\DomainEvent;

interface BusInterface {

	/**
	 * @param DomainEvent $event
	 * @return void
	 */
	public function publish(DomainEvent $event);

}