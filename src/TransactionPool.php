<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 10:43
	 */

	namespace MehrIt\LaraTransactions;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\Exception\InconsistentCommitException;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionBrokenException;
	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use MehrIt\LaraTransactions\Exception\TransactionStartedException;
	use Throwable;

	/**
	 * Manages multiple transactions
	 * @package ItsMieger\LaravelTransactions
	 */
	class TransactionPool implements Transaction
	{

		/**
		 * @var Transaction[]
		 */
		protected $transactions = [];



		/**
		 * Adds the given transaction to the pool
		 * @param Transaction $transaction The transaction
		 * @return $this
		 */
		public function add(Transaction $transaction) {
			$this->transactions[] = $transaction;

			return $this;
		}

		/**
		 * Gets all transactions managed by the pool
		 * @return Transaction[] The transactions
		 */
		public function getTransactions() {
			return $this->transactions;
		}


		/**
		 * Starts all pool transactions
		 * @return $this
		 * @throws RollbackException
		 * @throws TransactionStartedException Thrown if transaction was already started
		 */
		public function begin() {

			/** @noinspection PhpUnusedLocalVariableInspection */
			$success = false;
			try {
				foreach ($this->transactions as $curr) {
					$curr->begin();
				}

				$success = true;

				return $this;
			}
			finally {

				// on error, we rollback any transactions and throw the error again
				if (!$success)
					$this->rollback();
			}

		}

		/**
		 * Commits all transactions
		 * @return $this Thrown if a transaction was not started yet
		 * @throws TransactionNotStartedException Thrown if a transaction was not started yet
		 * @throws InconsistentCommitException Thrown if an inconsistent committed occurred (some connections were committed but others failed)
		 * @throws TransactionBrokenException Thrown if any connection was detected as broken before commit was executed
		 * @throws RollbackException
		 */
		public function commit() {

			// First we detect broken transactions before committing any. This is our best effort to avoid inconsistencies if working with multiple transactions.
			// We can skip broken transaction detection if there is only one transaction. It either fails or succeeds but never causes inconsistency.
			if (count($this->transactions) > 1) {

				// detect broken transactions
				$brokenTransaction = null;
				foreach (array_reverse($this->transactions) as $curr) {
					/** @var Transaction $curr */
					if (!$curr->test()) {
						$brokenTransaction = $curr;
						break;
					}
				}

				// stop if any transaction is broken
				if ($brokenTransaction) {

					// rollback all transactions
					$this->rollback();

					// throw broken exception
					throw new TransactionBrokenException($brokenTransaction);
				}
			}


			/** @var Transaction[] $transactions */
			$transactions = array_reverse($this->transactions);
			$firstTransaction = array_shift($transactions);
			$committedTransactions = [];

			// commit first transaction without any failure handling because exceptions should bubble up as normal
			if ($firstTransaction) {

				/** @noinspection PhpUnusedLocalVariableInspection */
				$success = false;
				try {
					$firstTransaction->commit();
					$committedTransactions[] = $firstTransaction;

					$success = true;
				}
				finally {
					if (!$success) {
						// rollback all other transactions
						$this->rollback();
					}
				}
			}


			// now we start committing the other transactions one after another
			try {
				foreach ($transactions as $curr) {
					$curr->commit();
					$committedTransactions[] = $curr;
				}
			}
			catch(Throwable $ex) {
				try {
					// rollback all other transactions
					$this->rollback();

					throw new InconsistentCommitException($committedTransactions, '', 0, $ex);
				}
				catch (RollbackException $rollbackEx) {

					report($ex);
					report($rollbackEx);

					throw new InconsistentCommitException($committedTransactions, '', 0, $ex);
				}
			}

			return $this;
		}

		/** @noinspection PhpDocMissingThrowsInspection */
		/**
		 * Rolls back the current transaction. It does nothing (even no error), if no transaction was started yet.
		 * @return $this
		 * @throws RollbackException Thrown if an error occurs while rollback
		 */
		public function rollback() {
			try {
				foreach (array_reverse($this->transactions) as $curr) {
					/** @var Transaction $curr */
					$curr->rollback();
				}
			}
			/** @noinspection PhpRedundantCatchClauseInspection */
			catch (RollbackException $ex) {
				throw $ex;
			}
			catch (Throwable $ex) {
				// convert to rollback exception
				throw new RollbackException('', 0, $ex);
			}

			return $this;
		}

		/**
		 * @inheritDoc
		 */
		public function test() {
			foreach ($this->transactions as $curr) {
				if (!$curr->test())
					return false;
			}

			return true;
		}


	}