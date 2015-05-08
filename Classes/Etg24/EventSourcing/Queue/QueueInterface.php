<?php
namespace Etg24\EventSourcing\Queue;

interface QueueInterface {

	/**
	 * @param Message $message
	 * @return void
	 */
	public function queue(Message $message);

}