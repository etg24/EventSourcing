<?php
namespace Etg24\EventSourcing\Projection\Hydration;

use TYPO3\Flow\Reflection\ObjectAccess;

class DtoHydrator implements HydratorInterface {

	/**
	 * @var string
	 */
	protected $dtoClassName;

	/**
	 * @param $dtoClassName
	 */
	public function __construct($dtoClassName) {
		$this->dtoClassName = $dtoClassName;
	}

	/**
	 * @param array $row
	 * @return object
	 */
	public function hydrate(array $row) {
		$dto = $this->getNewDtoInstance();

		foreach ($row as $column => $value) {
			if (ObjectAccess::isPropertySettable($dto, $column)) {
				ObjectAccess::setProperty($dto, $column, $value);
			}
		}

		return $dto;
	}

	/**
	 * @return object
	 */
	protected function getNewDtoInstance() {
		return new $this->dtoClassName();
	}

}