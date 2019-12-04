<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 15:50
	 */

	namespace MehrItLaraTransactionsTest\Cases\Transactors;

	use Illuminate\Database\Connection;
	use Illuminate\Database\Eloquent\Model;
	use MehrIt\LaraTransactions\TransactionPool;
	use MehrIt\LaraTransactions\Transactions\DatabaseTransaction;
	use MehrIt\LaraTransactions\Transactors\ModelTransactor;
	use MehrItLaraTransactionsTest\Cases\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;

	class ModelTransactorTest extends TestCase
	{
		public function testInvalidTypePassed() {

			/** @var TransactionPool $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();


			$transactor = new ModelTransactor();

			$this->expectException(\InvalidArgumentException::class);

			$transactor->bindToPool(new \stdClass(), $pool);
		}

		public function testConnectionNotInPoolYet() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');

			/** @var Model|MockObject $model */
			$model = $this->getMockBuilder(Model::class)->getMock();
			$model
				->method('getConnection')
				->willReturn($connection);

			/** @var TransactionPool|MockObject $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();
			$pool
				->method('getTransactions')
				->willReturn([]);
			$pool
				->expects($this->once())
				->method('add')
				->with(
					$this->callback(function ($v) use ($connection) {
						return
							$v instanceof DatabaseTransaction
							&& $v->getConnection() === $connection;
					})
				);

			$transactor = new ModelTransactor();

			$this->assertSame($transactor, $transactor->bindToPool($model, $pool));
		}

		public function testConnectionAlreadyInPool() {

			$dbTransaction = $this->getMockBuilder(DatabaseTransaction::class)->disableOriginalConstructor()->getMock();
			$dbTransaction
				->method('getConnectionName')
				->willReturn('testConnection');

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');

			/** @var Model|MockObject $model */
			$model = $this->getMockBuilder(Model::class)->getMock();
			$model
				->method('getConnection')
				->willReturn($connection);

			/** @var TransactionPool|MockObject $pool */
			$pool = $this->getMockBuilder(TransactionPool::class)->getMock();
			$pool
				->method('getTransactions')
				->willReturn([
					$dbTransaction,
				]);
			$pool
				->expects($this->never())
				->method('add');

			$transactor = new ModelTransactor();

			$this->assertSame($transactor, $transactor->bindToPool($model, $pool));
		}
	}