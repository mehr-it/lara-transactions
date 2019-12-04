<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 12:43
	 */

	namespace MehrIt\LaraTransactions\Transactors;


	use Illuminate\Database\DatabaseManager;
	use Illuminate\Database\Eloquent\Model;
	use MehrIt\LaraTransactions\TransactionPool;


	/**
	 * Transactor for models
	 * @package ItsMieger\LaravelTransactions\Transactors
	 */
	class ModelTransactor extends DatabaseConnectionTransactor
	{
		/**
		 * @inheritdoc
		 */
		public function bindToPool($obj, TransactionPool $pool) {

			if (!($obj instanceof Model))
				throw new \InvalidArgumentException('Expected ' . Model::class . ' as first argument. Got ' . ($obj ? get_class($obj) : 'null'));

			// get model's connection
			$connection = $obj->getConnection();

			return parent::bindToPool($connection, $pool);
		}

		/**
		 * Gets the application's DB manager
		 * @return DatabaseManager
		 */
		protected function getDbManager() {
			return app('db');
		}

	}