<?php
namespace Etg24\EventSourcing\Store\Backend;

use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Store\Backend\Exception\EventStreamNotFoundException;
use Etg24\EventSourcing\Store\Backend\Exception\OptimisticLockException;
use Etg24\EventSourcing\Store\Backend\Translator\WritableEventTranslator;
use EventStore\EventStore;
use EventStore\Exception\StreamNotFoundException;
use EventStore\Exception\WrongExpectedVersionException;
use EventStore\StreamFeed\Entry;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\LinkRelation;
use EventStore\WritableEventCollection;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventStoreBackend implements StoreBackendInterface {

	/**
	 * @var EventStore
	 */
	protected $eventStore;

	/**
	 * @var WritableEventTranslator
	 * @Flow\Inject
	 */
	protected $eventTranslator;

	/**
	 * @var string
	 * @Flow\Inject(setting="Store.Backend.EventStoreBackend.url")
	 */
	protected $eventStoreUrl;

	public function initializeObject() {
		$this->eventStore = new EventStore($this->eventStoreUrl);
	}

	/**
	 * @param string $identifier
	 * @param DomainEvent[] $changes
	 * @throws OptimisticLockException
	 */
	public function append($identifier, array $changes) {
		$events = [];
		$version = NULL;

		/** @var DomainEvent $event */
		foreach ($changes as $event) {
			if ($version === NULL) {
				$version = $event->version;
			}

			$events[] = $this->eventTranslator->toWritableEvent($event);
		}

		try {
			$this->eventStore->writeToStream(
				$identifier,
				new WritableEventCollection($events),
				$version -1
			);
		} catch (WrongExpectedVersionException $e) {
			throw new OptimisticLockException($e->getMessage(), 1427104266);
		}
	}

	/**
	 * @todo lazy loading
	 *
	 * @param string $eventStream
	 * @return DomainEvent[]
	 * @throws EventStreamNotFoundException
	 */
	public function load($eventStream) {
		try {
			$feed = $this->eventStore->openStreamFeed($eventStream, EntryEmbedMode::RICH());
		} catch (StreamNotFoundException $e) {
			throw new EventStreamNotFoundException($e->getMessage(), 1427104251);
		}

		if ($feed->hasLink(LinkRelation::LAST())) {
			$feed = $this->eventStore->navigateStreamFeed($feed, LinkRelation::LAST());
		} else {
			$feed = $this->eventStore->navigateStreamFeed($feed, LinkRelation::FIRST());
		}

		$rel = LinkRelation::PREVIOUS();

		$domainEvents = [];

		while ($feed !== NULL) {
			/** @var Entry[] $entries */
			$entries = array_reverse($feed->getEntries());

			foreach ($entries as $entry) {
				$event = $this->eventStore->readEvent(
					$entry->getEventUrl()
				);

				if ($event === NULL) {
					continue;
				}

				$domainEvent = $this->eventTranslator->fromEvent($event, $entry->getType());
				$domainEvent->version = $entry->getVersion();

				$domainEvents[] = $domainEvent;
			}

			$feed = $this->eventStore->navigateStreamFeed($feed, $rel);
		}

		return $domainEvents;
	}

}