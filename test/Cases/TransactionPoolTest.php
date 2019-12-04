<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 08:53
	 */

	namespace MehrItLaraTransactionsTest\Cases;


	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\Exception\InconsistentCommitException;
	use MehrIt\LaraTransactions\Exception\RollbackException;
	use MehrIt\LaraTransactions\Exception\TransactionBrokenException;
	use MehrIt\LaraTransactions\TransactionPool;
	use PHPUnit\Framework\MockObject\MockObject;

	class TransactionPoolTest extends TestCase
	{
		public function testAddGetTransaction() {

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();

			$pool = new TransactionPool();

			$this->assertSame($pool, $pool->add($t1));
			$this->assertEquals([$t1], $pool->getTransactions());

			$this->assertSame($pool, $pool->add($t2));
			$this->assertEquals([$t1, $t2], $pool->getTransactions());

		}

		public function testBegin() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->once())
				->method('begin')
				->willReturnCallback(function() use (&$lastCalled) {
					// check for correct order
					$this->assertEquals(null, $lastCalled, 'Expected to be called before other transactions');
					$lastCalled = 't1';
				})
			;
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('begin')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t1', $lastCalled, 'Expected t1 to be called before this transaction');
					$lastCalled = 't2';
				})
			;
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('begin')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2', $lastCalled, 'Expected t2 to be called before this transaction');
					$lastCalled = 't3';
				})
			;

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->assertSame($pool, $pool->begin());
		}

		public function testBegin_exceptionThrown() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->once())
				->method('begin')
			;
			$t1->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2', $lastCalled, 'Expected t2 to be called before this transaction');
					$lastCalled = 't1';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('begin')
				->willThrowException(new \Exception())
			;
			$t2->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3', $lastCalled, 'Expected t3 to be called before this transaction');
					$lastCalled = 't2';
				});
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->never())
				->method('begin')
			;
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals(null, $lastCalled, 'Expected to be called before other transactions');
					$lastCalled = 't3';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(\Exception::class);

			$pool->begin();
		}


		public function testRollback() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2', $lastCalled, 'Expected t2 to be called before this transaction');
					$lastCalled = 't1';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3', $lastCalled, 'Expected t3 to be called before this transaction');
					$lastCalled = 't2';
				});
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals(null, $lastCalled, 'Expected to be called before other transactions');
					$lastCalled = 't3';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->assertSame($pool, $pool->rollback());
		}

		public function testRollback_exceptionThrown() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2', $lastCalled, 'Expected t2 to be called before this transaction');
					$lastCalled = 't1';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('rollback')
				->willThrowException(new \Exception());
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals(null, $lastCalled, 'Expected to be called before other transactions');
					$lastCalled = 't3';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(RollbackException::class);
			$pool->rollback();
		}

		public function testRollback_rollbackExceptionThrown() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2', $lastCalled, 'Expected t2 to be called before this transaction');
					$lastCalled = 't1';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('rollback')
				->willThrowException(new RollbackException('test-m'));
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals(null, $lastCalled, 'Expected to be called before other transactions');
					$lastCalled = 't3';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(RollbackException::class);
			$this->expectExceptionMessage('test-m');
			$pool->rollback();
		}

		public function testTest_success() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->once())
				->method('test')
				->willReturn(true);
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('test')
				->willReturn(true);
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('test')
				->willReturn(true);

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->assertTrue($pool->test());
		}

		public function testTest_fail() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->method('test')
				->willReturn(true);
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->method('test')
				->willReturn(false);
			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->method('test')
				->willReturn(true);

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->assertFalse($pool->test());
		}

		public function testCommit() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->expects($this->once())
				->method('commit')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:commit', $lastCalled, 'Expected t2 to be committed before this transaction');
					$lastCalled = 't1:commit';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return true;
				});
			$t2->expects($this->once())
				->method('commit')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3:commit', $lastCalled, 'Expected t3 to be committed before this transaction');
					$lastCalled = 't2:commit';
				});

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('commit')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be committed before other transactions');
					$lastCalled = 't3:commit';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->assertSame($pool, $pool->commit());
		}

		public function testCommit_testFails() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->never())
				->method('commit');
			$t2->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return false;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3:rollback', $lastCalled, 'Expected t3 to be rolled back before this transaction');
					$lastCalled = 't2:rollback';
				});

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->never())
				->method('commit');
			$t3->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(TransactionBrokenException::class);
			$pool->commit();
		}

		public function testCommit_testFails_rollbackException() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->never())
				->method('commit');
			$t2->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return false;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willThrowException(new \Exception('test-m'));

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->never())
				->method('commit');
			$t3->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(RollbackException::class);
			$pool->commit();
		}

		public function testCommit_firstCommitFails() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->never())
				->method('commit');
			$t2->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return true;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3:rollback', $lastCalled, 'Expected t3 to be rolled back before this transaction');
					$lastCalled = 't2:rollback';
				});

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('commit')
				->willThrowException(new \Exception('test-m'))
			;
			$t3->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(\Exception::class);
			$this->expectExceptionMessage('test-m');
			$pool->commit();
		}

		public function testCommit_firstCommitFails_rollbackException() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->never())
				->method('commit');
			$t2->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return true;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willThrowException(new \Exception('test-m2'));

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('commit')
				->willThrowException(new \Exception('test-m'))
			;
			$t3->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(RollbackException::class);
			$pool->commit();
		}


		public function testCommit_failsAfterOtherTransactionWasCommitted() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('commit')
				->willThrowException(new \Exception('test-m'))
			;
			$t2->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return true;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t3:rollback', $lastCalled, 'Expected t3 to be rolled back before this transaction');
					$lastCalled = 't2:rollback';
				});

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('commit')
			;
			$t3->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(InconsistentCommitException::class);
			$pool->commit();
		}


		public function testCommit_failsAfterOtherTransactionWasCommitted_rollbackException() {

			$lastCalled = null;

			/** @var Transaction|MockObject $t1 */
			$t1 = $this->getMockBuilder(Transaction::class)->getMock();
			$t1->expects($this->never())
				->method('commit');
			$t1->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't1:test';

					return true;
				});
			$t1->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertEquals('t2:rollback', $lastCalled, 'Expected t2 to be rolled back before this transaction');
					$lastCalled = 't1:rollback';
				});
			/** @var Transaction|MockObject $t2 */
			$t2 = $this->getMockBuilder(Transaction::class)->getMock();
			$t2->expects($this->once())
				->method('commit')
				->willThrowException(new \Exception('test-m'))
			;
			$t2->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't2:test';

					return true;
				});
			$t2->expects($this->once())
				->method('rollback')
				->willThrowException(new \Exception());

			/** @var Transaction|MockObject $t3 */
			$t3 = $this->getMockBuilder(Transaction::class)->getMock();
			$t3->expects($this->once())
				->method('commit')
			;
			$t3->expects($this->once())
				->method('test')
				->willReturnCallback(function () use (&$lastCalled) {
					$lastCalled = 't3:test';

					return true;
				});
			$t3->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$lastCalled) {
					// check for correct order
					$this->assertContains(':test', $lastCalled, 'Expected t3 to be rolled back before other transactions');
					$lastCalled = 't3:rollback';
				});

			$pool = new TransactionPool();

			$pool->add($t1);
			$pool->add($t2);
			$pool->add($t3);

			$this->expectException(InconsistentCommitException::class);
			$pool->commit();
		}


	}