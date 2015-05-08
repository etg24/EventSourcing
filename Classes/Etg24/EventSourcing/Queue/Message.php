<?php
namespace Etg24\EventSourcing\Queue;

class Message {

	/**
	 * @var string
	 */
	protected $recipient;

	/**
	 * @var array
	 */
	protected $payload;

	/**
	 * @param string $recipient
	 * @param array $payload
	 */
	public function __construct($recipient, $payload) {
		$this->recipient = $recipient;
		$this->payload = $payload;
	}

	/**
	 * @return string
	 */
	public function getRecipient() {
		return $this->recipient;
	}

	/**
	 * @return array
	 */
	public function getPayload() {
		return $this->payload;
	}

}