# Transactions for Laravel
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mehr-it/lara-transactions.svg?style=flat-square)](https://packagist.org/packages/mehr-it/lara-transactions)
[![Build Status](https://travis-ci.org/mehr-it/lara-transactions.svg?branch=master)](https://travis-ci.org/mehr-it/lara-transactions)

Handles multiple simultaneous transactions and offers a general transaction interface for laravel.

Usually transactions are used when working with databases to ensure consistency. But this package
manages any kind of transactions (not only database) as long as the `Transaction`
interface is implemented.

 
## Installation

    composer require mehr-it/lara-transactions
	
This package uses Laravel's package auto-discovery, so the service provider and aliases will 
be loaded automatically.
	
	
## Managing transactions

The `Transaction` facade offers three basic method for transactions:

	Transaction::begin( /* transactional entities */);
	Transaction::commit();
	Transaction::rollback();
	
These methods behave like the corresponding database operations.

You may also use the `run()`-method which executes a callback wrapped in a transaction:

	Transaction::run( /* transactional entities */ , function() {
		// put your code here
	});
	
This first starts the transaction. Then the callback is executed and after that the transaction is
committed. If an Exception is thrown during callback execution, the transaction is rolled back and 
the exception is thrown.


### Starting transactions
Whenever you want to start a transaction, you first must list the entities which a transaction should
be created for. We call these "transactional entities". In the sense of database transactions the
transactional entities would be the database connections. You may pass in the connection instance or
the connection name:

	Transaction::begin(DB::connection());
	Transaction::begin('myConnection');
	Transaction::begin(['myConnection', 'anotherConnection']);
	Transaction::run('myConnection' , function() { /* ... */ });
	
As the example shows, it is possible to pass in multiple "transactional entities" to a single `begin()`
or `run()` call. For more information see "**Multi transaction handling**" below.
	
However you may also pass a model. The model's underlying database connection is automatically
detected and a transaction is started for it:
	
	Transaction::begin(MyModel::class);
	Transaction::begin([$user, $profile]);
	Transaction::run(MyModel::class , function() { /* ... */ });
	
If multiple models using the same database connection are passed, only one transaction is started.

#### Transactors
Sometimes entities do not implement their transactions on their own. Models are a good example: Their 
transactions are implemented by the underlying database connections. But it might be more handy to pass
in the models directly instead of passing the used database connection.
 
This is where "transactors" come in: A transactor creates the necessary transactions for a given
entity and adds them to the pool of manages transactions. For database connections and models, the
corresponding transactors are available by default. However you are free to implement your own.

Simply implement the `Transactor` interface and register your transactor:

	Transaction::registerTransactor(EntityClass::class, TransactorClass::class);
	

### Multi transaction handling

The `Transaction` facade allows to pass in multiple "transactional entities" for which multiple
transactions may be created and managed at the same time.

The problem with multiple transactions is, that it can not be assured that all of them commit or fail:
They have to be committed one after another. If one commit succeeds and subsequent one fails,
inconsistencies may happen. You should avoid multiple transactions wherever possible. But real world
examples show, that it is not avoidable in some scenarios. Think of applications requiring multiple
databases or using different storage systems.

This problem can not be solved, but we could try to minimize the risk. And of course we should report
whenever inconsistent commits happen.

Our best effort to minimize the risk of inconsistent commits, is to check each transaction to be
"alive" right before the first transaction is committed. Only if no transaction seams to be broken,
we start committing them one after another. As the time gap between the first transaction being
tested and the last one being committed is very low, the risk of a transaction breaking in between
is as low as possible. Of course this implies commit operations to be fast and robust (fail rarely).

For database transactions the "alive" testing is done by executing a simple "SELECT 1", to see if
the server session is still intact.


### Nesting transactions

You may start new transactions while other transactions are open. Following snippet demonstrates this:


	Transaction::run([EntityA::class, EntityB::class] , function() {
		
		// CodeBlock1
		
		Transaction::run(EntityC::class , function() {
			
			// CodeBlock2
			
		});
		
		// CodeBlock3
	});
	
This example works as expected: Between CodeBlock1 and CodeBlock3 another transaction is established.
It is started after CodeBlock1 and committed before CodeBlock3 is reached. As long as all entities use
the same underlying transaction implementation which supports nesting, there is nothing to worry about.
This is true for most database connections. 

But you have to be careful if EntityC uses a transaction which is independent from the outer entities'
transaction(s) (different transaction implementation or other database connection): Failures in the
outer transaction would not cause the inner transaction to be rolled back. That's because it's
underlying transaction is not nested within the outer underlying transactions.
