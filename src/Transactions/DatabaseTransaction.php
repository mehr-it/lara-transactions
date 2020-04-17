<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 09:45
	 */

	namespace MehrIt\LaraTransactions\Transactions;


	use Illuminate\Database\Connection;
	use Illuminate\Database\DetectsLostConnections;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Str;
	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use MehrIt\LaraTransactions\Exception\TransactionStartedException;

	class DatabaseTransaction implements Transaction
	{
		use DetectsLostConnections;

		protected $omitTableForTestSelect = false;

		/**
		 * @var Connection
		 */
		protected $connection;

		/**
		 * @var bool
		 */
		protected $transactionStarted = false;

		protected $rollbackToLevel = 0;

		/**
		 * Creates a new instance
		 * @param Connection $connection The database connection to handle transaction for
		 */
		public function __construct(Connection $connection) {
			$this->connection = $connection;
		}


		/**
		 * Gets the DB connection name
		 * @return null|string The DB connection name
		 */
		public function getConnectionName() {
			return $this->connection->getName();
		}

		/**
		 * Gets the managed database connection
		 * @return Connection The connection
		 */
		public function getConnection() {
			return $this->connection;
		}


		/**
		 * @inheritdoc
		 */
		public function begin() {
			if ($this->transactionStarted)
				throw new TransactionStartedException();

			$connection = $this->connection;

			// remember level to rollback to
			$this->rollbackToLevel = $connection->transactionLevel();

			$connection->beginTransaction();
			$this->transactionStarted = true;

			return $this;
		}

		/**
		 * @inheritdoc
		 */
		public function commit() {
			if (!$this->transactionStarted)
				throw new TransactionNotStartedException();

			$this->connection->commit();
			$this->transactionStarted = false;

			return $this;
		}

		/**
		 * @inheritdoc
		 */
		public function rollback() {
			if ($this->transactionStarted) {

				try {
					$this->connection->rollBack($this->rollbackToLevel);
				}
				catch(\Exception $ex) {
					// ignore connection loss errors, since a collection loss implicitly executes a rollback
					if (!$this->causedByLostConnection($ex))
						throw new RollbackException(null, 0, $ex);
				}
				catch(\Throwable $ex) {
					throw new RollbackException(null, 0, $ex);
				}
				$this->transactionStarted = false;
			}

			return $this;
		}

		/**
		 * @inheritdoc
		 */
		public function test() {
			// our best effort here is to check if the connection is still alive by executing a dummy query

			// no transaction started yet, not ready
			if (!$this->transactionStarted)
				return false;

			$connection = $this->connection;

			if ($this->rollbackToLevel != $connection->transactionLevel() - 1)
				return false;

			try {

				// we do a dummy query, to test if the connection is still alive
				// (for oracle databases we have to use the dual table)
				if (Str::contains(Str::lower($this->connection->getDriverName()), [
					'oracle',
					'oci'
				])) {
					// dummy select using dual table
					$connection->select('SELECT 1 FROM dual');
				}
				else {
					// dummy select without table
					$connection->select('SELECT 1');
				}

				// select could be executed => connection is alive
				return true;
			}
			catch(QueryException $ex) {

				// is the error caused by a lost connection?
				if ($this->causedByLostConnection($ex))
					return false;

				// we got another error, the test did not work
				throw new \RuntimeException('Connection alive test for "' . $this->getConnectionName() . '" failed due to unexpected error.', 0, $ex);
			}
		}


	}