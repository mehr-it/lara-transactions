<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 15:54
	 */

	namespace MehrItLaraTransactionsTest\Cases\Transactions;


	use Illuminate\Database\Connection;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Facades\DB;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionNotStartedException;
	use MehrIt\LaraTransactions\Exception\TransactionStartedException;
	use MehrIt\LaraTransactions\Transactions\DatabaseTransaction;
	use MehrItLaraTransactionsTest\Cases\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;
	use Yajra\Oci8\Oci8ServiceProvider;

	class DatabaseTransactionTest extends TestCase
	{

		public function testConstructorGetters() {


			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();


			$trans = new DatabaseTransaction($connection);

			$this->assertSame($connection, $trans->getConnection());
			$this->assertEquals(DB::connection()->getName(), $trans->getConnectionName());
		}


		public function testBegin() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$trans = new DatabaseTransaction($connection);


			$this->assertSame($trans, $trans->begin());

			$this->assertSame(1, DB::transactionLevel());
		}

		public function testBegin_alreadyStarted() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$trans = new DatabaseTransaction($connection);


			// first call should succeed
			$trans->begin();

			$this->assertSame(1, DB::transactionLevel());

			// second call should fail
			$this->expectException(TransactionStartedException::class);
			$trans->begin();
		}

		public function testBegin_exceptionThrown() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$thrownException = new \Exception();

			$pdoMock = $this->getMockBuilder(\PDO::class)->disableOriginalConstructor()->getMock();
			$pdoMock
				->expects($this->once())
				->method('beginTransaction')
				->willThrowException($thrownException);

			$connection->setPdo($pdoMock);


			$trans = new DatabaseTransaction($connection);

			try {
				$trans->begin();

				$this->fail('The expected exception was not thrown');
			}
			catch (\Exception $ex) {
				$this->assertSame($thrownException, $ex);
			}

		}


		public function testCommit() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$trans = new DatabaseTransaction($connection);

			$trans->begin();

			$this->assertSame(1, DB::connection()->transactionLevel());

			$this->assertSame($trans, $trans->commit());

			$this->assertSame(0, DB::connection()->transactionLevel());
		}

		public function testCommit_callsCommitMethod() {

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

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$thrownException = new \Exception();

			$pdoMock = $this->getMockBuilder(\PDO::class)->disableOriginalConstructor()->getMock();
			$pdoMock
				->expects($this->once())
				->method('commit')
				->willThrowException($thrownException);

			$connection->setPdo($pdoMock);

			$trans = new DatabaseTransaction($connection);
			$trans->begin();

			try {
				$trans->commit();

				$this->fail('The expected exception was not thrown');
			}
			catch (\Exception $ex) {
				$this->assertSame($thrownException, $ex);
			}
		}


		public function testRollback() {

			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

			$trans = new DatabaseTransaction($connection);

			$trans->begin();

			$this->assertSame(1, DB::connection()->transactionLevel());

			$this->assertSame($trans, $trans->rollback());

			$this->assertSame(0, DB::connection()->transactionLevel());
		}

		public function testRollback_callsRollbackMethod() {

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

		public function testRollback_savepointNotExistsException() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');
			$connection
				->expects($this->once())
				->method('rollBack')
				->willThrowException(new \Exception('SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT trans2 does not exist'));
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


			if (!env('DB_CONNECTION'))
				$this->markTestSkipped('No database connection configured');

			/** @var Connection|MockObject $connection */
			$connection = DB::connection();

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

			$level = 0;
			$connection
				->method('beginTransaction')
				->willReturnCallback(function () use (&$level) {
					++$level;
				});
			$connection
				->method('transactionLevel')
				->willReturnCallback(function () use (&$level) {
					return $level;
				});

			$trans = new DatabaseTransaction($connection);


			$trans->begin();

			$this->assertFalse($trans->test());
		}

		public function testTest_connectionDeadlockRollback() {

			/** @var Connection|MockObject $connection */
			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
			$connection
				->method('getName')
				->willReturn('testConnection');

			$level = 0;
			$connection
				->method('beginTransaction')
				->willReturnCallback(function () use (&$level) {
					++$level;
				});
			$connection
				->method('transactionLevel')
				->willReturnCallback(function () use (&$level) {
					return $level;
				});

			$trans = new DatabaseTransaction($connection);


			$trans->begin();

			// simulate dead lock rollback (Laravel decreases the transaction level in such case)
			--$level;

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

			$level = 0;
			$connection
				->method('beginTransaction')
				->willReturnCallback(function () use (&$level) {
					++$level;
				});
			$connection
				->method('transactionLevel')
				->willReturnCallback(function () use (&$level) {
					return $level;
				});

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

			if (!class_exists(Oci8ServiceProvider::class))
				$this->markTestSkipped('Oci8ServiceProvider not installed');

			if (!env('DB_ORACLE_HOST') && !env('DB_ORACLE_TNS'))
				$this->markTestSkipped('No oracle database connection configured');

			$trans = new DatabaseTransaction(DB::connection('oracleTesting'));

			$trans->begin();

			$this->assertTrue($trans->test());

		}


	}