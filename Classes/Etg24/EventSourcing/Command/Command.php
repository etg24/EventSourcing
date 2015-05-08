<?php
namespace Etg24\EventSourcing\Command;

use Etg24\EventSourcing\Uuid;

class Command implements CommandInterface {

	/**
	 * @var string
	 */
	public $commandId;

	public function __construct() {
		$this->commandId = Uuid::next();
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this) . ':' . $this->commandId;
	}

}