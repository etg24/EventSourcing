<?php
namespace Etg24\EventSourcing\Projection\Hydration;

interface HydratorInterface {

	/**
	 * @param array $row
	 * @return mixed
	 */
	public function hydrate(array $row);

}