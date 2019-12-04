<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 10:46
	 */

	namespace MehrIt\LaraTransactions\Exception;


	use Throwable;

	class TransactionStartedException extends \Exception
	{
		/**
		 * @inheritDoc
		 */
		public function __construct($message = "", $code = 0, Throwable $previous = null) {

			if (!$message)
				$message = 'Transaction already started';

			parent::__construct($message, $code, $previous);
		}

	}