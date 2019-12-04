<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 15:23
	 */

	namespace MehrItLaraTransactionsTest\Cases;

	use MehrIt\LaraTransactions\Provider\TransactionsProvider;
	use Orchestra\Testbench\TestCase as OrchestraTestCase;
	use Yajra\Oci8\Oci8ServiceProvider;


	class TestCase extends OrchestraTestCase
	{
		/**
		 * Define environment setup.
		 *
		 * @param  \Illuminate\Foundation\Application $app
		 * @return void
		 */
		protected function getEnvironmentSetUp($app) {
			// setup testing connection for oracle
			$app['config']->set('database.connections.oracleTesting', [
				'driver'   => 'oracle',
				'tns'            => env('DB_ORACLE_TNS', ''),
				'host'           => env('DB_ORACLE_HOST', ''),
				'port'           => env('DB_ORACLE_PORT', '1521'),
				'database'       => env('DB_ORACLE_DATABASE', ''),
				'username'       => env('DB_ORACLE_USERNAME', ''),
				'password'       => env('DB_ORACLE_PASSWORD', ''),
				'charset'        => env('DB_ORACLE_CHARSET', 'WE8ISO8859P15'),
				'prefix'         => env('DB_ORACLE_PREFIX', ''),
				'prefix_schema'  => env('DB_ORACLE_SCHEMA_PREFIX', ''),
				'server_version' => env('DB_ORACLE_SERVER_VERSION', '11g'),
			]);
		}

		/**
		 * Load package service provider
		 * @param  \Illuminate\Foundation\Application $app
		 * @return array
		 */
		protected function getPackageProviders($app) {

			$ret = [TransactionsProvider::class];

			if (class_exists(Oci8ServiceProvider::class))
				$ret[] = Oci8ServiceProvider::class;

			return $ret;
		}
	}