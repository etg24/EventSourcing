<?php
namespace Etg24\EventSourcing\Queue;

use Pheanstalk\Pheanstalk;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class BeanstalkQueue implements QueueInterface {

	/**
	 * @var Pheanstalk
	 * @Flow\Inject
	 */
	protected $pheanstalk;

	/**
	 * @var string
	 * @Flow\Inject(setting="EventBus.Queue.BeanstalkQueue.tube")
	 */
	protected $tube;

	/**
	 * @param Message $message
	 * @return void
	 */
	public function queue(Message $message) {
		$data = json_encode([
			'recipient' => $message->getRecipient(),
			'payload' => $message->getPayload()
		]);

		$this->pheanstalk
			->useTube($this->tube)
			->put($data);
	}

}