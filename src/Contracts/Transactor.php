<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 12:37
	 */

	namespace MehrIt\LaraTransactions\Contracts;


	use MehrIt\LaraTransactions\TransactionPool;

	/**
	 * Creates transactional operations for given objects
	 * @package ItsMieger\LaravelTransactions\Contracts
	 */
	interface Transactor
	{
		/**
		 * Adds a transactional operation for the given object to the pool
		 * @param object $obj The object to create transactional operation for
		 * @param TransactionPool $pool The pool which manages the transactional operation
		 * @return $this
		 */
		public function bindToPool($obj, TransactionPool $pool);
	}