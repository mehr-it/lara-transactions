<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.09.18
	 * Time: 13:28
	 */

	namespace MehrItLaraTransactionsTest\Cases\Provider;

	use Illuminate\Database\Connection;
	use Illuminate\Database\Eloquent\Model;
	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\TransactionManager;
	use MehrItLaraTransactionsTest\Cases\TestCase;

	class TransactionsProviderTest extends TestCase
	{

		public function testTransactionManagerRegistration() {

			/** @var TransactionManager $resolvedManager */
			$resolvedManager = resolve(TransactionManager::class);

			$this->assertInstanceOf(TransactionManager::class, $resolvedManager);
			$this->assertSame($resolvedManager, resolve(TransactionManager::class));

			$modelMock = $this->getMockBuilder(Model::class)->getMock();
			$modelMock->method('getConnection')
				->willReturn($this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock());

			// check that default transactors are added (would throw exception if not registered)
			$resolvedManager->begin($this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock());
			$resolvedManager->begin($modelMock);
			$resolvedManager->begin($this->getMockBuilder(Transaction::class)->getMock());
		}

	}