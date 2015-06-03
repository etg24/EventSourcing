<?php
namespace Etg24\EventSourcing;

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The EventSourcing Package
 */
class Package extends BasePackage {

	/**
	 * @var boolean
	 */
	protected $protected = TRUE;

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();

		# Command Auditing
		$dispatcher->connect('Etg24\EventSourcing\Command\Bus\InternalCommandBus', 'commandHandlingSuccess', 'Etg24\EventSourcing\Auditing\CommandLogger', 'onCommandHandlingSuccess');
		$dispatcher->connect('Etg24\EventSourcing\Command\Bus\InternalCommandBus', 'commandHandlingFailure', 'Etg24\EventSourcing\Auditing\CommandLogger', 'onCommandHandlingFailure');

		# Event Auditing
		$dispatcher->connect('Etg24\EventSourcing\Event\Bus\InternalEventBus', 'eventHandlingSuccess', 'Etg24\EventSourcing\Auditing\EventLogger', 'onEventHandlingSuccess');
		$dispatcher->connect('Etg24\EventSourcing\Event\Bus\InternalEventBus', 'eventHandlingFailure', 'Etg24\EventSourcing\Auditing\EventLogger', 'onEventHandlingFailure');
		$dispatcher->connect('Etg24\EventSourcing\Event\Bus\InternalEventBus', 'eventQueueingSuccess', 'Etg24\EventSourcing\Auditing\EventLogger', 'onEventQueueingSuccess');

	}
}
