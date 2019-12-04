<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 15:54
	 */

	namespace MehrItLaraTransactionsTest\Cases\Transactions;


	use Illuminate\Database\Connection;
	use Illuminate\Database\Events\QueryExecuted;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Facades\DB;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use MehrIt\LaraTransactions\Exception\TransactionStartedException;
	use MehrIt\LaraTransactions\Transactions\DatabaseTransaction;
	use MehrItLaraTransactionsTest\Cases\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;

	class DatabaseTransactionTest extends TestCase
	{

		public function testConstructorGetters() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');


			$trans = new DatabaseTransaction($connection);

			$this->assertSame($connection, $trans->getConnection());
			$this->assertEquals('testConnection', $trans->getConnectionName());
		}


		public function testBegin() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('beginTransaction');

			$trans = new DatabaseTransaction($connection);


			$this->assertSame($trans, $trans->begin());
		}

		public function testBegin_alreadyStarted() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('beginTransaction');

			$trans = new DatabaseTransaction($connection);


			// first call should succeed
			$trans->begin();

			// second call should fail
			$this->expectException(TransactionStartedException::class);
			$trans->begin();
		}

		public function testBegin_exceptionThrown() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('beginTransaction')
				->willThrowException(new \Exception())
			;

			$trans = new DatabaseTransaction($connection);


			$this->expectException(\Exception::class);
			$trans->begin();

		}


		public function testCommit() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('beginTransaction');
			$connection
				->expects($this->once())
				->method('commit');

			$trans = new DatabaseTransaction($connection);

			$trans->begin();
			$this->assertSame($trans, $trans->commit());
		}

		public function testCommit_yetNotStarted() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->never())
				->method('commit');

			$trans = new DatabaseTransaction($connection);

			$this->expectException(TransactionNotStartedException::class);
			$trans->commit();
		}


		public function testCommit_exceptionThrown() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('commit')
				->willThrowException(new \Exception())
			;

			$trans = new DatabaseTransaction($connection);
			$trans->begin();

			$this->expectException(\Exception::class);
			$trans->commit();
		}


		public function testRollback() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('beginTransaction');
			$connection
				->expects($this->once())
				->method('rollBack');

			$trans = new DatabaseTransaction($connection);
			$trans->begin();

			$this->assertSame($trans, $trans->rollback());
		}

		public function testRollback_yetNotStarted() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->never())
				->method('rollBack');

			$trans = new DatabaseTransaction($connection);


			$this->assertSame($trans, $trans->rollback());
		}

		public function testRollback_connectionLossException() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('rollBack')
				->willThrowException(new \Exception('Mysql server has gone away'));
			;

			$trans = new DatabaseTransaction($connection);
			$trans->begin();

			$this->assertSame($trans, $trans->rollback());
		}

		public function testRollback_exceptionThrown() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('rollBack')
				->willThrowException(new \Exception());
			;

			$trans = new DatabaseTransaction($connection);

			$this->expectException(RollbackException::class);
			$trans->begin();

			$this->assertSame($trans, $trans->rollback());
		}

		public function testTest_success() {


			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('select')
				->with('SELECT 1');

			$trans = new DatabaseTransaction($connection);


			$trans->begin();

			$this->assertTrue($trans->test());
		}

		public function testTest_failConnectionLoss() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('select')
				->willThrowException(new QueryException('', [], new \Exception('server has gone away')));

			$trans = new DatabaseTransaction($connection);


			$trans->begin();

			$this->assertFalse($trans->test());
		}

		public function testTest_exceptionThrown() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('select')
				->willThrowException(new QueryException('', [], new \Exception('s.th. went wrong')));

			$trans = new DatabaseTransaction($connection);


			$trans->begin();

			$this->expectException(\RuntimeException::class);
			$trans->test();
		}

		public function testTest_success_usingTestingConnection() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			$trans = new DatabaseTransaction(DB::connection());

			$trans->begin();

			$this->assertTrue($trans->test());
		}


		public function testTest_success_usingOracleTestingConnection() {

			if (!env('DB_ORACLE_HOST') && !env('DB_ORACLE_TNS'))
				$this->markTestSkipped('No oracle database connection configured');

			$trans = new DatabaseTransaction(DB::connection('oracleTesting'));

			$trans->begin();

			$this->assertTrue($trans->test());

		}


	}