<?php
namespace Etg24\EventSourcing;

use TYPO3\Flow\Utility\Algorithms;

class Uuid {

	static public function next() {
		return Algorithms::generateUUID();
	}

	protected function __construct() {}

}