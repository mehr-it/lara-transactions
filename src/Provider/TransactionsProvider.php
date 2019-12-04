<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 09:21
	 */

	namespace MehrIt\LaraTransactions\Provider;


	use Carbon\Laravel\ServiceProvider;
	use Illuminate\Contracts\Support\DeferrableProvider;
	use Illuminate\Database\Connection;
	use Illuminate\Database\Eloquent\Model;
	use MehrIt\LaraTransactions\Contracts\Transaction;
	use MehrIt\LaraTransactions\TransactionManager;
	use MehrIt\LaraTransactions\Transactors\DatabaseConnectionTransactor;
	use MehrIt\LaraTransactions\Transactors\ModelTransactor;
	use MehrIt\LaraTransactions\Transactors\TransactionTransactor;

	class TransactionsProvider extends ServiceProvider implements DeferrableProvider
	{
		/**
		 * Register the service provider.
		 *
		 * @return void
		 */
		public function register() {

			// register transaction manager
			$this->app->singleton(TransactionManager::class, function ($app) {
				$manager = new TransactionManager(array_keys($app['config']['database.connections'] ?? []));

				// add default transactors
				$manager->registerTransactor(Model::class, ModelTransactor::class);
				$manager->registerTransactor(Connection::class, DatabaseConnectionTransactor::class);
				$manager->registerTransactor(Transaction::class, TransactionTransactor::class);

				return $manager;
			});

		}

		/**
		 * Get the services provided by the provider.
		 *
		 * @return array
		 */
		public function provides() {
			return [
				TransactionManager::class
			];
		}
	}