<?php
namespace Etg24\EventSourcing\Projection;

use Etg24\EventSourcing\Projection\Annotations\Column;
use Etg24\EventSourcing\Projection\Annotations\Table;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * Builds a projection for a given annotated DTO
 *
 * @Flow\Scope("singleton")
 */
class ProjectionBuilder {

	/**
	 * @var Connection
	 */
	protected $databaseConnection;

	/**
	 * @param ObjectManager $objectManager
	 */
	public function injectObjectManager(ObjectManager $objectManager) {
		$this->databaseConnection = $objectManager->getConnection();
	}

	/**
	 * @var ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @param string $className
	 */
	public function build($className) {
		if ($this->reflectionService->isClassAnnotatedWith($className, Table::class) === FALSE) {
			throw new \InvalidArgumentException('The class "' . $className . '" is not annotated properly.', 1428331094);
		}

		/** @var Table $table */
		$table = $this->reflectionService->getClassAnnotation($className, Table::class);
		$query = $this->createTableDefinitionQueryForClassName($className);

		$this->databaseConnection->executeQuery('DROP TABLE IF EXISTS ' . $table->name);
		$this->databaseConnection->executeQuery($query);
	}

	/**
	 * @param string $className
	 * @return string
	 */
	public function showQuery($className) {
		if ($this->reflectionService->isClassAnnotatedWith($className, Table::class) === FALSE) {
			throw new \InvalidArgumentException('The class "' . $className . '" is not annotated properly.', 1428332546);
		}

		return $this->createTableDefinitionQueryForClassName($className);
	}

	/**
	 * @param string $className
	 * @return string
	 */
	protected function createTableDefinitionQueryForClassName($className) {
		/** @var Table $table */
		$table = $this->reflectionService->getClassAnnotation($className, Table::class);

		$columns = $this->reflectionService->getPropertyNamesByAnnotation($className, Column::class);

		$columnDefinitions = [];
		foreach ($columns as $columnName) {
			/** @var Column $column */
			$column = $this->reflectionService->getPropertyAnnotation($className, $columnName, Column::class);
			$columnDefinitions[] = '`' . $columnName . '` ' . $column->definition;
		}

		if ($table->indexes !== NULL) {
			$columnDefinitions[] = $table->indexes;
		}

		return 'CREATE TABLE ' . $table->name . ' (' . implode(', ', $columnDefinitions) . ') ENGINE = InnoDB';
	}

}