<?php


	namespace MehrIt\LaraTransactions\Contracts;


	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use MehrIt\LaraTransactions\Exception\TransactionStartedException;

	/**
	 * Implements a transactional operation
	 * @package ItsMieger\LaravelTransactions\Contracts
	 */
	interface Transaction
	{
		/**
		 * Starts a new transaction
		 * @throws TransactionStartedException Thrown if transaction was already started
		 * @return $this
		 */
		public function begin();

		/**
		 * Commits the current transaction.
		 * @throws TransactionNotStartedException Thrown if transaction was not started yet
		 * @return $this
		 */
		public function commit();

		/**
		 * Rolls back the current transaction. It does nothing (even no error), if no transaction was started yet.
		 * @return $this
		 */
		public function rollback();

		/**
		 * Checks if the transaction is still ready for committing
		 * @return bool True if can be committed. Else false.
		 */
		public function test();
	}