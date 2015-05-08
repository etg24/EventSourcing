<?php
namespace Etg24\EventSourcing\Projection;

use Etg24\EventSourcing\Projection\Annotations\Table;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 */
abstract class AbstractMysqlProjector extends AbstractProjector {

	/**
	 * @var Connection
	 */
	protected $databaseConnection;

	/**
	 * @var ProjectionBuilder
	 * @Flow\Inject
	 */
	protected $projectionBuilder;

	/**
	 * @var ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->databaseConnection = $objectManager->getConnection();
	}

	protected function initializeObject() {
		/** @var Table $table */
		$table = $this->reflectionService->getClassAnnotation($this->projectionClassName, Table::class);

		if ($table !== NULL) {
			$this->tableName = $table->name;
		}
	}

	/**
	 * Clears and rebuilds the projection persistence structure
	 *
	 * @return void
	 */
	public function build() {
		$this->projectionBuilder->build($this->projectionClassName);
	}

	/**
	 * @param string $identifier
	 * @return NULL|object
	 */
	public function findById($identifier) {
		$rows = $this->databaseConnection->fetchAll('SELECT * FROM ' . $this->tableName . ' WHERE id = :id LIMIT 1', ['id' => $identifier]);
		$firstResult = array_shift($rows);

		if ($firstResult === NULL) {
			return NULL;
		}

		return $this->hydrator->hydrate($firstResult);
	}

	/**
	 * @return object[]
	 */
	public function findAll() {
		$rows = $this->databaseConnection->fetchAll('SELECT * FROM ' . $this->tableName);
		return $this->hydrateResult($rows);
	}

	/**
	 * @return integer
	 */
	public function countAll() {
		$rows = $this->databaseConnection->fetchAll('SELECT COUNT(*) AS row_count FROM ' . $this->tableName);
		return (integer) $rows[0]['row_count'];
	}

	/**
	 * @param string $identifier
	 */
	public function deleteById($identifier) {
		$statement = $this->databaseConnection->prepare(
			'DELETE FROM ' . $this->tableName . ' WHERE id = :id'
		);

		$statement->execute([
			'id' => $identifier
		]);
	}

}