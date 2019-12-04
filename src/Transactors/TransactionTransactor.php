<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 11:35
	 */

	namespace MehrIt\LaraTransactions\Transactors;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\Contracts\Transactor;
	use MehrIt\LaraTransactions\TransactionPool;

	class TransactionTransactor implements Transactor
	{
		/**
		 * @inheritDoc
		 */
		public function bindToPool($obj, TransactionPool $pool) {

			if (!($obj instanceof Transaction))
				throw new \InvalidArgumentException('Expected ' . Transaction::class . ' as first argument. Got ' . ($obj ? get_class($obj) : 'null'));

			// check if a DB transaction for the connection is already in the pool
			$transactionExistsInPool = false;
			foreach ($pool->getTransactions() as $currTransaction) {
				if ($currTransaction === $obj) {
					$transactionExistsInPool = true;
					break;
				}

			}

			if (!$transactionExistsInPool)
				$pool->add($obj);

			return $this;
		}

	}