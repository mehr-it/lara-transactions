<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 13:01
	 */

	namespace MehrIt\LaraTransactions\Transactors;


	use Illuminate\Database\Connection;
	use MehrIt\LaraTransactions\Contracts\Transactor;
	use MehrIt\LaraTransactions\TransactionPool;
	use MehrIt\LaraTransactions\Transactions\DatabaseTransaction;

	/**
	 * Transactor for database connections
	 * @package ItsMieger\LaravelTransactions\Transactors
	 */
	class DatabaseConnectionTransactor implements Transactor
	{
		/**
		 * @inheritdoc
		 */
		public function bindToPool($obj, TransactionPool $pool) {

			if (!($obj instanceof Connection))
				throw new \InvalidArgumentException('Expected ' . Connection::class . ' as first argument. Got ' . ($obj ? get_class($obj) : 'null'));

			// get connection's name
			$connectionName = $obj->getName();

			// check if a DB transaction for the connection is already in the pool
			$transactionExistsInPool = false;
			foreach ($pool->getTransactions() as $currTransaction) {
				if ($currTransaction instanceof DatabaseTransaction && $currTransaction->getConnectionName() == $connectionName) {
					$transactionExistsInPool = true;
					break;
				}

			}

			// create new DB transaction in pool if yet not existing
			if (!$transactionExistsInPool)
				$pool->add(new DatabaseTransaction($obj));

			return $this;
		}

	}