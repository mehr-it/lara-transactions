<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 13:12
	 */

	namespace MehrIt\LaraTransactions;


	use Closure;
	use MehrIt\LaraTransactions\Contracts\Transactor;
	use MehrIt\LaraTransactions\Exception\InconsistentCommitException;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionBrokenException;
	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use RuntimeException;

	class TransactionManager
	{
		/**
		 * @var TransactionPool[]
		 */
		protected $stack = [];

		/**
		 * @var Transactor[]|string[]
		 */
		protected $transactors = [];

		/**
		 * @var string[]
		 */
		protected $databaseConnectionNames;

		/**
		 * Creates a new instance
		 * @param string[] $databaseConnectionNames The names of available database connections
		 */
		public function __construct(array $databaseConnectionNames = []) {
			$this->databaseConnectionNames = $databaseConnectionNames;
		}


		/**
		 * Starts a new transaction for all the given transactional(s)
		 * @param string[]|object[]|string|object $transactional The transactional(s). Accepts database connection names, class names or instances.
		 * @return $this
		 */
		public function begin($transactional) {

			if (!is_array($transactional))
				$transactional = [$transactional];


			$this->stack[] = $pool = app(TransactionPool::class);

			// bind all transactions to pool
			foreach($transactional as $curr) {
				$this->bindToPool($curr, $pool);
			}

			$pool->begin();

			return $this;
		}

		/**
		 * Commits the transactions for the last begun transactions
		 * @return $this
		 * @throws InconsistentCommitException Thrown if an inconsistent committed occurred (some connections were committed but others failed)
		 * @throws TransactionBrokenException Thrown if any connection was detected as broken before commit was executed
		 * @throws TransactionNotStartedException
		 * @throws RollbackException
		 */
		public function commit() {
			/** @var TransactionPool|null $pool */
			$pool = end($this->stack);

			if (!$pool)
				throw new TransactionNotStartedException();

			try {
				$pool->commit();
			}
			finally {
				array_pop($this->stack);
			}

			return $this;
		}

		/**
		 * Rolls back the last begun transactions
		 * @throws RollbackException Thrown if an error occurs while rollback
		 * @return $this
		 */
		public function rollback() {
			/** @var TransactionPool|null $pool */
			$pool = end($this->stack);

			if ($pool) {
				try {
					$pool->rollback();
				}
				finally {
					array_pop($this->stack);
				}
			}

			return $this;
		}

		/**
		 * Executes the given callback within transactions for the given transactional(s)
		 * @param string[]|object[]|string|object $transactional The transactional(s). Accepts database connection names, class names or instances.
		 * @param callable $callback The callback which should be executed within the transactions
		 * @return mixed The callback return value
		 * @throws InconsistentCommitException Thrown if an inconsistent committed occurred (some connections were committed but others failed)
		 * @throws TransactionBrokenException Thrown if any connection was detected as broken before commit was executed
		 * @throws RollbackException Thrown if an error occurs while rollback
		 * @throws TransactionNotStartedException
		 */
		public function run($transactional, callable $callback) {


			/** @noinspection PhpUnusedLocalVariableInspection */
			$success = false;
			try {

				// begin transaction
				$this->begin($transactional);

				// run callback
				$ret = call_user_func($callback);

				$success = true;

				return $ret;
			}
			finally {
				if ($success)
					$this->commit();
				else
					$this->rollback();

			}
		}

		/**
		 * Registers a new transactor for a given class
		 * @param string $handledClass The class which the transactor handles
		 * @param Transactor|Closure|string $transactor The transactor to register
		 * @return $this
		 */
		public function registerTransactor(string $handledClass, $transactor) {
			$this->transactors[$handledClass] = $transactor;

			return $this;
		}

		/**
		 * Binds the given transactional to the given pool
		 * @param string|object $obj The transactional
		 * @param TransactionPool $pool The pool to bind to
		 */
		protected function bindToPool($obj, TransactionPool $pool) {

			// resolve obj to a transactional instance
			$obj = $this->resolveTransactionalInstance($obj);

			// find transactor
			$transactor = null;
			$transactorClass = null;
			foreach($this->transactors as $cls => $curr) {

				if ($obj instanceof $cls) {
					$transactor = $curr;
					$transactorClass = $cls;
					break;
				}
			}
			if (!$transactor)
				throw new RuntimeException('Could not find transactor for ' . get_class($obj));

			// make transactor instance if yet not resolved
			if (!($transactor instanceof Transactor))
				$this->transactors[$transactorClass] = $transactor = app($transactor);

			// bind to pool
			$transactor->bindToPool($obj, $pool);
		}

		/**
		 * Resolves the given value to a transactional instance
		 * @param string|object $obj The transactional
		 * @return mixed
		 */
		protected function resolveTransactionalInstance($obj) {
			// resolve strings
			if (is_string($obj)) {

				// if string is database connection name, we return the database connection
				if (in_array($obj, $this->databaseConnectionNames))
					return app('db')->connection($obj);

				// resolve name
				return app($obj);
			}

			return $obj;
		}


	}