<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 11:38
	 */

	namespace MehrItLaraTransactionsTest\Cases\Transactors;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\TransactionPool;
	use MehrIt\LaraTransactions\Transactors\TransactionTransactor;
	use MehrItLaraTransactionsTest\Cases\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;

	class TransactionTransactorTest extends TestCase
	{
		public function testInvalidTypePassed() {

			/** @var TransactionPool $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();


			$transactor = new TransactionTransactor();

			$this->expectException(\InvalidArgumentException::class);

			$transactor->bindToPool(new \stdClass(), $pool);
		}

		public function testConnectionNotInPoolYet() {

			/** @var Transaction|MockObject $transaction */
			$transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();

			/** @var TransactionPool|MockObject $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();
			$pool
				->method('getTransactions')
				->willReturn([]);
			$pool
				->expects($this->once())
				->method('add')
				->with(
					$this->callback(function ($v) use ($transaction) {
						return $v === $transaction;
					})
				);

			$transactor = new TransactionTransactor();

			$this->assertSame($transactor, $transactor->bindToPool($transaction, $pool));
		}

		public function testConnectionAlreadyInPool() {

			/** @var Transaction|MockObject $transaction */
			$transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();

			/** @var TransactionPool|MockObject $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();
			$pool
				->method('getTransactions')
				->willReturn([
					$transaction,
				]);
			$pool
				->expects($this->never())
				->method('add');

			$transactor = new TransactionTransactor();

			$this->assertSame($transactor, $transactor->bindToPool($transaction, $pool));
		}
	}