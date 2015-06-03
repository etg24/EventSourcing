<?php
namespace Etg24\EventSourcing\Store;

use Etg24\EventSourcing\AggregateRootInterface;
use Etg24\EventSourcing\Domain\Model\ObjectName;
use Etg24\EventSourcing\Event\Bus\BusInterface;
use Etg24\EventSourcing\Store\Backend\Exception\EventStreamNotFoundException;
use Etg24\EventSourcing\Store\Backend\Exception\OptimisticLockException;
use Etg24\EventSourcing\Store\Backend\StoreBackendInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
abstract class Repository {

	/**
	 * @var StoreBackendInterface
	 * @Flow\Inject
	 */
	protected $backend;

	/**
	 * @var BusInterface
	 * @Flow\Inject
	 */
	protected $eventBus;

	/**
	 * @var string
	 */
	protected $aggregateClassName;

	/**
	 * @var ObjectName
	 */
	protected $streamName;

	public function __construct() {
		$this->aggregateClassName = preg_replace(array('/\\\Repository\\\/', '/Repository$/'), array('\\Model\\', ''), get_class($this));
		$this->streamName = new ObjectName($this->aggregateClassName);
	}

	/**
	 * @param string $identifier
	 * @return NULL|AggregateRootInterface
	 */
	public function find($identifier) {
		try {
			$stream = $this->getStreamForIdentifier($identifier);
			$eventStream = $this->backend->load($stream);

			$aggregateClass = $this->aggregateClassName;
			$aggregate = $aggregateClass::loadFromEventStream($eventStream);

			return $aggregate;
		} catch (EventStreamNotFoundException $e) {
			return NULL;
		}
	}

	/**
	 * @param AggregateRootInterface $aggregate
	 * @throws OptimisticLockException
	 */
	public function save(AggregateRootInterface $aggregate) {
		if ($aggregate instanceof $this->aggregateClassName === FALSE) {
			throw new \InvalidArgumentException('The given object ("' . get_class($aggregate) . '") is not an aggregate this repository manages ("' . $this->aggregateClassName . '").', 1427115916);
		}

		if (trim($aggregate->getIdentifier()) === '') {
			throw new \InvalidArgumentException('The identifier for the given aggregate "' . get_class($aggregate) . '" must not be empty.');
		}

		$stream = $this->getStreamForIdentifier($aggregate->getIdentifier());
		$changes = $aggregate->getUncommittedChanges();
		$this->backend->append($stream, $changes);

		foreach ($changes as $change) {
			$this->eventBus->publish($change);
		}

		$aggregate->markChangesAsCommitted();
	}

	/**
	 * @param string $identifier
	 * @return string
	 */
	protected function getStreamForIdentifier($identifier) {
		return sprintf('%s-%s', $this->streamName, $identifier);
	}

}