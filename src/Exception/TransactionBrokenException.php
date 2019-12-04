<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 11:09
	 */

	namespace MehrIt\LaraTransactions\Exception;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use Throwable;

	class TransactionBrokenException extends \Exception
	{
		protected $transaction;

		/**
		 * @inheritDoc
		 */
		public function __construct(Transaction $transaction, $message = "", $code = 0, Throwable $previous = null) {

			$this->transaction = $transaction;

			if (!$message)
				$message = 'Transaction is broken';

			parent::__construct($message, $code, $previous);
		}

		/**
		 * @return Transaction
		 */
		public function getTransaction(): Transaction {
			return $this->transaction;
		}


	}