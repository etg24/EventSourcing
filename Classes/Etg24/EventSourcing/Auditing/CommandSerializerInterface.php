<?php
namespace Etg24\EventSourcing\Auditing;

use Etg24\EventSourcing\Command\Command;

interface CommandSerializerInterface {

	/**
	 * @param Command $command
	 * @return array
	 */
	public function serialize(Command $command);

}