<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 11:34
	 */

	namespace MehrIt\LaraTransactions\Exception;


	use Throwable;

	class RollbackException extends \Exception
	{
		/**
		 * @inheritDoc
		 */
		public function __construct($message = "", $code = 0, Throwable $previous = null) {

			if (!$message)
				$message = 'An exception was thrown while transaction rollback';

			parent::__construct($message, $code, $previous);
		}
	}