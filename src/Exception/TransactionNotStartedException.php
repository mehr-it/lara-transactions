<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 10:49
	 */

	namespace MehrIt\LaraTransactions\Exception;


	use Throwable;

	class TransactionNotStartedException extends \Exception
	{
		/**
		 * @inheritDoc
		 */
		public function __construct($message = "", $code = 0, Throwable $previous = null) {

			if (!$message)
				$message = 'Transaction not started';

			parent::__construct($message, $code, $previous);
		}

	}