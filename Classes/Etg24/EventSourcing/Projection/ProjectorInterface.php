<?php
namespace Etg24\EventSourcing\Projection;

use Etg24\EventSourcing\Event\Handler\EventHandlerInterface;

interface ProjectorInterface extends EventHandlerInterface {

	/**
	 * @return string FQCN of the Projection class
	 */
	public function getProjectionClassName();

	/**
	 * Clears and rebuilds the projection persistence structure
	 *
	 * @return void
	 */
	public function build();

	/**
	 * @param string $identifier
	 * @return object
	 */
	public function findById($identifier);

	/**
	 * @return integer
	 */
	public function countAll();

}