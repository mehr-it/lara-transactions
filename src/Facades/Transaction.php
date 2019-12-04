<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 12.09.18
	 * Time: 14:33
	 */

	namespace MehrIt\LaraTransactions\Facades;


	use Illuminate\Support\Facades\Facade;
	use MehrIt\LaraTransactions\TransactionManager;

	class Transaction extends Facade
	{

		/**
		 * Get the registered name of the component.
		 *
		 * @return string
		 */
		protected static function getFacadeAccessor() {
			return TransactionManager::class;
		}

	}