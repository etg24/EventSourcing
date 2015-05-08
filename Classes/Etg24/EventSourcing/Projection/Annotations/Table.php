<?php
namespace Etg24\EventSourcing\Projection\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Table {

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $indexes;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if (isset($values['name']) || isset($values['value'])) {
			$this->name = isset($values['name']) ? $values['name'] : $values['value'];
		}

		if (isset($values['indexes'])) {
			$this->indexes = $values['indexes'];
		}
	}

}