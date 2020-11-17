<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 10:28
	 */

	namespace MehrItLaraTransactionsTest\Cases;


	use Illuminate\Database\Connection;
	use Illuminate\Database\DatabaseManager;
	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\Contracts\Transactor;
	use MehrIt\LaraTransactions\TransactionManager;
	use MehrIt\LaraTransactions\TransactionPool;
	use PHPUnit\Framework\ExpectationFailedException;
	use PHPUnit\Framework\MockObject\MockObject;

	class TransactionManagerTest extends TestCase
	{
		public function testBegin_single() {

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->expects($this->once())
				->method('begin');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});
			;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$tm->begin($transactable);

		}

		public function testBegin_noTransactor() {

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$tm = new TransactionManager();


			$this->expectException(\RuntimeException::class);
			$this->expectExceptionMessageRegExp('/^Could not find transactor.*/');

			$tm->begin($transactable);

		}

		public function testBegin_resolvedUsingServiceContainer() {

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->expects($this->once())
				->method('begin');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});;

			$tm = new TransactionManager();

			app()->bind('testCls', function() use ($transactable) {
				return $transactable;
			});

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$tm->begin('testCls');

		}

		public function testBegin_dbConnectionName() {

			$connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->expects($this->once())
				->method('begin');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($connection, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {

					$pool->add($transaction);

					return $this;
				});;

			$dbMock = $this->getMockBuilder(DatabaseManager::class)->disableOriginalConstructor()->getMock();
			$dbMock
				->method('connection')
				->willReturn($connection);

			$tm = new TransactionManager(['testDbConnection']);

			app()->bind('db', function() use ($dbMock) {
				return $dbMock;
			});

			$tm->registerTransactor(Connection::class, $mockTransactor);

			$tm->begin('testDbConnection');

		}

		public function testBegin_multiple() {

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->expects($this->once())
				->method('begin');
			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->expects($this->once())
				->method('begin');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(2))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function($t, $pool) use ($transactable1, $transaction1, $transaction2) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					else
						$pool->add($transaction2);

					return $this;
				});
			;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$tm->begin([$transactable1, $transactable2]);

		}


		public function testCommit_single() {

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->expects($this->once())
				->method('begin');
			$transaction
				->expects($this->once())
				->method('commit');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin($transactable);

			$tm->commit();

		}

		public function testCommit_multiple() {

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->expects($this->once())
				->method('begin');
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->expects($this->once())
				->method('commit');
			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->expects($this->once())
				->method('begin');
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->expects($this->once())
				->method('commit');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(2))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transaction1, $transaction2) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					else
						$pool->add($transaction2);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin([$transactable1, $transactable2]);

			$tm->commit();
		}

		public function testCommit_nested() {

			$t1Committed = false;
			$t2Committed = false;
			$t3Committed = false;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable3 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->expects($this->once())
				->method('begin');
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->expects($this->once())
				->method('commit')
				->willReturnCallback(function() use (&$t1Committed) {
					$t1Committed = true;
				})
			;

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->expects($this->once())
				->method('begin');
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->expects($this->once())
				->method('commit')
				->willReturnCallback(function () use (&$t2Committed) {
					$t2Committed = true;
				})
			;

			$transaction3 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction3
				->expects($this->once())
				->method('begin');
			$transaction3
				->method('test')
				->willReturn(true);
			$transaction3
				->expects($this->once())
				->method('commit')
				->willReturnCallback(function () use (&$t3Committed) {
					$t3Committed = true;
				})
			;


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(3))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transactable2, $transaction1, $transaction2, $transaction3) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					elseif ($t === $transactable2)
						$pool->add($transaction2);
					else
						$pool->add($transaction3);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin([$transactable1, $transactable2]);
			$tm->begin([$transactable3]);

			// commit t3
			$tm->commit();
			$this->assertFalse($t1Committed);
			$this->assertFalse($t2Committed);
			$this->assertTrue($t3Committed);

			// commit t1 + t2
			$tm->commit();
			$this->assertTrue($t1Committed);
			$this->assertTrue($t2Committed);
			$this->assertTrue($t3Committed);
		}


		public function testRollback_single() {

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->expects($this->once())
				->method('begin');
			$transaction
				->expects($this->once())
				->method('rollback');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin($transactable);

			$tm->rollback();

		}

		public function testRollback_multiple() {

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->expects($this->once())
				->method('begin');
			$transaction1
				->expects($this->once())
				->method('rollback');
			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->expects($this->once())
				->method('begin');
			$transaction2
				->expects($this->once())
				->method('rollback');


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(2))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transaction1, $transaction2) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					else
						$pool->add($transaction2);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin([$transactable1, $transactable2]);

			$tm->rollback();
		}

		public function testRollback_nested() {

			$t1RolledBack = false;
			$t2RolledBack = false;
			$t3RolledBack = false;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable3 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->expects($this->once())
				->method('begin');
			$transaction1
				->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$t1RolledBack) {
					$t1RolledBack = true;
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->expects($this->once())
				->method('begin');
			$transaction2
				->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$t2RolledBack) {
					$t2RolledBack = true;
				});

			$transaction3 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction3
				->expects($this->once())
				->method('begin');
			$transaction3
				->expects($this->once())
				->method('rollback')
				->willReturnCallback(function () use (&$t3RolledBack) {
					$t3RolledBack = true;
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(3))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transactable2, $transaction1, $transaction2, $transaction3) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					elseif ($t === $transactable2)
						$pool->add($transaction2);
					else
						$pool->add($transaction3);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);
			$tm->begin([$transactable1, $transactable2]);
			$tm->begin([$transactable3]);

			// rollback t3
			$tm->rollback();
			$this->assertFalse($t1RolledBack);
			$this->assertFalse($t2RolledBack);
			$this->assertTrue($t3RolledBack);

			// rollback t1 + t2
			$tm->rollback();
			$this->assertTrue($t1RolledBack);
			$this->assertTrue($t2RolledBack);
			$this->assertTrue($t3RolledBack);
		}


		public function testRun_single() {

			$t1State = null;

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->method('begin')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'begun';
				})
			;
			$transaction
				->method('commit')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'committed';
				})
			;
			$transaction
				->method('rollback')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'rolled back';
				})
			;


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$callbackCalled = false;
			$tm->run($transactable, function() use (&$callbackCalled, &$t1State) {
				$this->assertEquals('begun', $t1State);
				$callbackCalled = true;
			});

			$this->assertTrue($callbackCalled);
			$this->assertEquals('committed', $t1State);

		}

		public function testRun_single_exception() {

			$t1State = null;

			$transactable = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction
				->method('begin')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'begun';
				})
			;
			$transaction
				->method('commit')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'committed';
				})
			;
			$transaction
				->method('rollback')
				->willReturnCallback(function() use (&$t1State) {
					$t1State = 'rolled back';
				})
			;


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->once())
				->method('bindToPool')
				->with($transactable, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transaction) {
					$pool->add($transaction);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$callbackCalled = false;
			try {
				$tm->run($transactable, function () use (&$callbackCalled, &$t1State) {
					$this->assertEquals('begun', $t1State);
					$callbackCalled = true;

					throw new \Exception();
				});

				$this->assertFalse(true);
			}
			catch (\Exception $ex) {
				if ($ex instanceof ExpectationFailedException)
					throw $ex;

				$this->assertFalse(false);
			}

			$this->assertTrue($callbackCalled);
			$this->assertEquals('rolled back', $t1State);

		}

		public function testRun_multiple() {

			$t1State = null;
			$t2State = null;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->method('begin')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'begun';
				});
			$transaction1
				->method('commit')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'committed';
				});
			$transaction1
				->method('rollback')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'rolled back';
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->method('begin')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'begun';
				});
			$transaction2
				->method('commit')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'committed';
				});
			$transaction2
				->method('rollback')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'rolled back';
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(2))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transaction1, $transaction2) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					else
						$pool->add($transaction2);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$callbackCalled = false;
			$tm->run([$transactable1, $transactable2], function () use (&$callbackCalled, &$t1State, &$t2State) {
				$this->assertEquals('begun', $t1State);
				$this->assertEquals('begun', $t2State);
				$callbackCalled = true;
			});

			$this->assertTrue($callbackCalled);
			$this->assertEquals('committed', $t1State);
			$this->assertEquals('committed', $t2State);
		}

		public function testRun_multiple_exception() {

			$t1State = null;
			$t2State = null;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->method('begin')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'begun';
				});
			$transaction1
				->method('commit')
				->willReturnCallback(function () use (&$t1State) {
					$this->assertSame('begun', $t1State);

					$t1State = 'committed';
				});
			$transaction1
				->method('rollback')
				->willReturnCallback(function () use (&$t1State) {
					$this->assertSame('begun', $t1State);

					$t1State = 'rolled back';
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->method('begin')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'begun';
				});
			$transaction2
				->method('commit')
				->willReturnCallback(function () use (&$t2State) {
					$this->assertSame('begun', $t2State);

					$t2State = 'committed';
				});
			$transaction2
				->method('rollback')
				->willReturnCallback(function () use (&$t2State) {
					$this->assertSame('begun', $t2State);

					$t2State = 'rolled back';
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(2))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transaction1, $transaction2) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					else
						$pool->add($transaction2);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);

			$callbackCalled = false;
			try {
				$tm->run([$transactable1, $transactable2], function () use (&$callbackCalled, &$t1State, &$t2State) {
					$this->assertEquals('begun', $t1State);
					$this->assertEquals('begun', $t2State);
					$callbackCalled = true;

					throw new \Exception();
				});

				$this->assertFalse(true);
			}
			catch (\Exception $ex) {
				if ($ex instanceof ExpectationFailedException)
					throw $ex;

				$this->assertFalse(false);
			}

			$this->assertTrue($callbackCalled);
			$this->assertEquals('rolled back', $t1State);
			$this->assertEquals('rolled back', $t2State);
		}


		public function testRun_nested() {

			$t1State = null;
			$t2State = null;
			$t3State = null;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable3 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->method('begin')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'begun';
				});
			$transaction1
				->method('commit')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'committed';
				});
			$transaction1
				->method('rollback')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'rolled back';
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->method('begin')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'begun';
				});
			$transaction2
				->method('commit')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'committed';
				});
			$transaction2
				->method('rollback')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'rolled back';
				});

			$transaction3 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction3
				->method('test')
				->willReturn(true);
			$transaction3
				->method('begin')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'begun';
				});
			$transaction3
				->method('commit')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'committed';
				});
			$transaction3
				->method('rollback')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'rolled back';
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(3))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transactable2, $transaction1, $transaction2, $transaction3) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					elseif ($t === $transactable2)
						$pool->add($transaction2);
					else
						$pool->add($transaction3);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);


			$callbackCalledA = false;
			$callbackCalledB = false;
			$tm->run([$transactable1, $transactable2], function () use (&$callbackCalledA, &$callbackCalledB, &$t1State, &$t2State, &$t3State, $tm, $transactable3) {
				$this->assertEquals('begun', $t1State);
				$this->assertEquals('begun', $t2State);
				$this->assertEquals(null, $t3State);
				$callbackCalledA = true;

				$tm->run([$transactable3], function () use (&$callbackCalledB, &$t3State, $tm) {
					$this->assertEquals('begun', $t3State);
					$callbackCalledB = true;
				});

				$this->assertTrue($callbackCalledB);
				$this->assertEquals('committed', $t3State);
			});

			$this->assertTrue($callbackCalledA);
			$this->assertTrue($callbackCalledB);
			$this->assertEquals('committed', $t1State);
			$this->assertEquals('committed', $t2State);
			$this->assertEquals('committed', $t3State);
		}


		public function testRun_nested_exceptionInner() {

			$t1State = null;
			$t2State = null;
			$t3State = null;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable3 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'begun';
				});
			$transaction1
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'committed';
				});
			$transaction1
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'rolled back';
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'begun';
				});
			$transaction2
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'committed';
				});
			$transaction2
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'rolled back';
				});

			$transaction3 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction3
				->method('test')
				->willReturn(true);
			$transaction3
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'begun';
				});
			$transaction3
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'committed';
				});
			$transaction3
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'rolled back';
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(3))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transactable2, $transaction1, $transaction2, $transaction3) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					elseif ($t === $transactable2)
						$pool->add($transaction2);
					else
						$pool->add($transaction3);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);


			$callbackCalledA = false;
			$callbackCalledB = false;
			try {
				$tm->run([$transactable1, $transactable2], function () use (&$callbackCalledA, &$callbackCalledB, &$t1State, &$t2State, &$t3State, $tm, $transactable3) {
					$this->assertEquals('begun', $t1State);
					$this->assertEquals('begun', $t2State);
					$this->assertEquals(null, $t3State);
					$callbackCalledA = true;

					try {
						$tm->run([$transactable3], function () use (&$callbackCalledB, &$t3State, $tm) {
							$this->assertEquals('begun', $t3State);
							$callbackCalledB = true;

							throw new \Exception();
						});

						$this->assertFalse(true);
					}
					catch (\Exception $ex) {
						if ($ex instanceof ExpectationFailedException)
							throw $ex;

						$this->assertFalse(false);

						throw $ex;
					}


					$this->assertTrue($callbackCalledB);
					$this->assertEquals('rolled back', $t3State);
				});

				$this->assertFalse(true);
			}
			catch (\Exception $ex) {
				if ($ex instanceof ExpectationFailedException)
					throw $ex;

				$this->assertFalse(false);
			}


			$this->assertTrue($callbackCalledA);
			$this->assertTrue($callbackCalledB);
			$this->assertEquals('rolled back', $t1State);
			$this->assertEquals('rolled back', $t2State);
			$this->assertEquals('rolled back', $t3State);
		}

		public function testRun_nested_exceptionOuter() {

			$t1State = null;
			$t2State = null;
			$t3State = null;

			$transactable1 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable2 = $this->getMockBuilder(\stdClass::class)->getMock();
			$transactable3 = $this->getMockBuilder(\stdClass::class)->getMock();

			$transaction1 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction1
				->method('test')
				->willReturn(true);
			$transaction1
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'begun';
				});
			$transaction1
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'committed';
				});
			$transaction1
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t1State) {
					$t1State = 'rolled back';
				});

			$transaction2 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction2
				->method('test')
				->willReturn(true);
			$transaction2
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'begun';
				});
			$transaction2
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'committed';
				});
			$transaction2
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t2State) {
					$t2State = 'rolled back';
				});

			$transaction3 = $this->getMockBuilder(Transaction::class)->getMock();
			$transaction3
				->method('test')
				->willReturn(true);
			$transaction3
				->expects($this->atMost(1))
				->method('begin')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'begun';
				});
			$transaction3
				->expects($this->atMost(1))
				->method('commit')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'committed';
				});
			$transaction3
				->expects($this->atMost(1))
				->method('rollback')
				->willReturnCallback(function () use (&$t3State) {
					$t3State = 'rolled back';
				});


			/** @var Transactor|MockObject $mockTransactor */
			$mockTransactor = $this->getMockBuilder(Transactor::class)->getMock();
			$mockTransactor
				->expects($this->exactly(3))
				->method('bindToPool')
				->with($transactable1, $this->isInstanceOf(TransactionPool::class))
				->willReturnCallback(function ($t, $pool) use ($transactable1, $transactable2, $transaction1, $transaction2, $transaction3) {
					if ($t === $transactable1)
						$pool->add($transaction1);
					elseif ($t === $transactable2)
						$pool->add($transaction2);
					else
						$pool->add($transaction3);

					return $this;
				});;

			$tm = new TransactionManager();

			$tm->registerTransactor(MockObject::class, $mockTransactor);


			$callbackCalledA = false;
			$callbackCalledB = false;
			try {
				$tm->run([$transactable1, $transactable2], function () use (&$callbackCalledA, &$callbackCalledB, &$t1State, &$t2State, &$t3State, $tm, $transactable3) {
					$this->assertEquals('begun', $t1State);
					$this->assertEquals('begun', $t2State);
					$this->assertEquals(null, $t3State);
					$callbackCalledA = true;

					$tm->run([$transactable3], function () use (&$callbackCalledB, &$t3State, $tm) {
						$this->assertEquals('begun', $t3State);
						$callbackCalledB = true;
					});


					$this->assertTrue($callbackCalledB);
					$this->assertEquals('committed', $t3State);

					throw new \Exception();
				});

				$this->assertFalse(true);
			}
			catch (\Exception $ex) {
				if ($ex instanceof ExpectationFailedException)
					throw $ex;

				$this->assertFalse(false);
			}


			$this->assertTrue($callbackCalledA);
			$this->assertTrue($callbackCalledB);
			$this->assertEquals('rolled back', $t1State);
			$this->assertEquals('rolled back', $t2State);
			$this->assertEquals('committed', $t3State);
		}
	}