<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 11:27
	 */

	namespace MehrIt\LaraTransactions\Exception;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use Throwable;

	class InconsistentCommitException extends \Exception
	{
		protected $transactions;

		/**
		 * Construct the exception. Note: The message is NOT binary safe.
		 * @link http://php.net/manual/en/exception.construct.php
		 * @param Transaction[] $committedTransactions The committed transactions
		 * @param string $message [optional] The Exception message to throw.
		 * @param int $code [optional] The Exception code.
		 * @param Throwable $previous [optional] The previous throwable used for the exception chaining.
		 * @since 5.1.0
		 */
		public function __construct(array $committedTransactions, $message = "", $code = 0, Throwable $previous = null) {

			$this->transactions = $committedTransactions;

			if (!$message)
				$message = 'A commit of multiple transactions failed after ' . count($committedTransactions) . ' transaction(s) were already committed.';

			parent::__construct($message, $code, $previous);
		}

		/**
		 * @return Transaction[]
		 */
		public function getTransactions() {
			return $this->transactions;
		}

	}