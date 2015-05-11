<?php
namespace Etg24\EventSourcing\Projection;

use Etg24\EventSourcing\Event\DomainEvent;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Persistence\QueryInterface;

/**
 * @Flow\Scope("singleton")
 */
abstract class AbstractModelProjector extends AbstractProjector {

	/**
	 * @var PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @param DomainEvent $event
	 * @throws \Etg24\EventSourcing\Event\Handler\Exception\UnableToHandleEventException
	 */
	public function handle(DomainEvent $event) {
		parent::handle($event);
		$this->persistenceManager->persistAll();
	}

	/**
	 * Clears and rebuilds the projection persistence structure
	 *
	 * @return void
	 */
	public function build() {
		foreach ($this->findAll() AS $object) {
			$this->deleteById(
				$this->persistenceManager->getIdentifierByObject($object)
			);
		}
	}

	/**
	 * @param string $identifier
	 * @return NULL|object
	 */
	public function findById($identifier) {
		return $this->persistenceManager->getObjectByIdentifier($identifier, $this->projectionClassName);
	}

	/**
	 * @return object[]
	 */
	public function findAll() {
		return $this->createQuery()
			->execute()
			->toArray();
	}

	/**
	 * @return integer
	 */
	public function countAll() {
		return $this->createQuery()
			->count();
	}

	/**
	 * @param string $identifier
	 */
	public function deleteById($identifier) {
		$object = $this->findById($identifier);

		// todo: check if we want to throw an exception here
		if ($object !== NULL) {
			$this->persistenceManager->remove($object);
			$this->persistenceManager->persistAll();
		}
	}

	/**
	 * @return QueryInterface
	 */
	protected function createQuery() {
		return $this->persistenceManager->createQueryForType($this->projectionClassName);
	}

}