<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 13:47
	 */

	namespace MehrItLaraTransactionsTest\Cases\Facades;


	use MehrIt\LaraTransactions\Facades\Transaction;
	use MehrIt\LaraTransactions\TransactionManager;
	use MehrItLaraTransactionsTest\Cases\TestCase;

	class TransactionTest extends TestCase
	{

		public function testAncestorCall() {

			$managerMock = $this->getMockBuilder(TransactionManager::class)->getMock();
			$managerMock
				->expects($this->once())
				->method('rollback');


			app()->singleton(TransactionManager::class, function() use ($managerMock) {
				return $managerMock;
			});

			Transaction::rollback();
		}
	}