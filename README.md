# Laravel Payment Gateways
Payment handling for the Laravel application (PayU).

### Payment gateways
- PayU - with Official OpenPayU PHP Library - https://payu.com

### Create Payu sandbox
https://secure.snd.payu.com/cp/register?lang=pl

## Install

### Composer package
```sh
composer require breakermind/pay "~1.0.0"
```

### Install package
```sh
# update
composer update
# refresh
composer dump-autoload -o
```

## Laravel app setup

### Create models
```sh
php artisan make:model Order
php artisan make:model Client
```

### Add in Order model
```php
<?php
namespace App\Models;

use Pay\Models\Order as PaymentOrder;

class Order extends PaymentOrder
{
	protected $guarded = [];
}
```

### Add in Client model
```php
<?php
namespace App\Models;

use Pay\Models\Client as PaymentClient;

class Client extends PaymentClient
{
	protected $guarded = [];
}
```

### Database and user
mysql -u root
```sql
CREATE DATABASE IF NOT EXISTS laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laravel.* TO root@localhost IDENTIFIED BY 'toor' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON laravel.* TO root@127.0.0.1 IDENTIFIED BY 'toor' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

### Config .env
```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=toor
```

### Create database tables
```sh
# only create newly added migrations
php artisan migrate

# create all migrations and seed
php artisan migrate:fresh --seed

# package seeder (create demo orders)
php artisan db:seed --class="\Database\Seeders\PayDatabaseSeeder"
```

### Create payment config
```sh
# create payu config/pay.php file
php artisan vendor:publish --tag=pay-config
```

### Configure gateways api in config/pay.php
```php
<?php

return [
	'version' => 1.0,

	// Load routes
	'routes' => true,

	// Load db migrations
	'migrations' => true,

	// Enable payments
	'enable' => [
		'payu' => true,
	],

	// Payu api credentials
	'payu' => [
		// Environment production: 'secure' for development: 'sandbox'
		'env' => 'sandbox',

		// Keys
		'pos_id' => '',
		'pos_md5' => '',

		// Oauth
		'client_id' => '',
		'client_secret' => '',

		// Currency
		'currency' => 'PLN',
	],
];
```

### Refresh public icons (optional)
```sh
php artisan vendor:publish --tag=pay-public --force
```

### Customize payment success and error page (optional)
```sh
# create custom error pages resources/views/vendor/pay
php artisan vendor:publish --tag=pay-pages

# or and select tag pay-pages
php artisan vendor:publish --provider="Pay\PayServiceProvider.php" --tag=pay-pages
```

# Usage

### Create payment url
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Create payment url
	$url = Pay::pay('payu', Order::findOrFail($order_uid));

	// Redirect client to payment page
	return redirect($url);

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Accept (waiting) payment
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Accept waiting payment
	$status = Pay::confirm('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Cancel (waiting) payment
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Cancel payment
	$status = Pay::cancel('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Refresh payment status
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Refresh payment
	$status = Pay::refresh('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Retrive payment details
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Payment details
	$payment = Pay::retrive('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Retrive payment transaction details
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Payment transaction details
	$transaction = Pay::transaction('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Refund payment
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Refresh payment
	$status = Pay::refund('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Refund payment details
```php
<?php
use App\Models\Order;
use Pay\Facades\Pay;

try {
	// Order uid
	$order_uid = 'uid-order-id-here';

	// Refresh payment
	$status = Pay::refunds('payu', Order::findOrFail($order_uid));

} catch (\Exception $e) {
	return $e->getMessage();
}
```

### Orders list (dev)
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Order;

Route::get('/orders', function () {
	// Orders with payment details
	return Order::with('payment')->orderBy('created_at', 'desc')->get();

	// Orders with payment and client details
	return Order::with('payment','client')->orderBy('created_at', 'desc')->get();

	// Limit payment columns
	return Order::with(['payment' => function($query) {
		$query->select('id','order_uid','total','status','status_refund','created_at')->orderBy('created_at', 'desc');
	}])->orderBy('created_at', 'desc')->get();
});
```

### Events
```php
// After payment url has been created
use Pay\Events\PaymentCreated;

// After waiting payment has been canceled
use Pay\Events\PaymentCanceled;

// After waiting payment has been accepted
use Pay\Events\PaymentConfirmed;

// After payment refund has been created
use Pay\Events\PaymentRefunded;
```

### Payment methods
- https://github.com/breakermind/pay/blob/main/src/Pay.php
- https://github.com/breakermind/pay/blob/main/src/Http/Controllers/PaymentController.php


# Development

### Create package migration
```sh
php artisan make:migration UpdatePayTables
```

### Update package migration tables
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePayTables extends Migration
{
	public function up()
	{
		Schema::table('orders', function (Blueprint $table) {
			// Columns
			if (!Schema::hasColumn('orders', 'user_id')) {
				$table->unsignedBigInteger('user_id')->nullable(true)->after('uid');
			}

			// Indexes
			$table->index('user_id');
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
		});

		Schema::table('clients', function (Blueprint $table) {
			$table->string('zip', 10)->nullable(true)->after('country');
		});
	}

	public function down()
	{
		Schema::table('orders', function (Blueprint $table) {
			// Indexes
			$table->dropForeign('orders_user_id_foreign');
			$table->dropIndex('orders_user_id_index');

			// Drop columns
			$table->dropColumn([
				'user_id'
			]);
		});

		Schema::table('clients', function (Blueprint $table) {
			// Drop columns
			$table->dropColumn([
				'zip'
			]);
		});
	}
}
```

### Update migrations
```sh
php artisan migrate
```

### Set in .env, .env.testing
```sh
# Default Storage::disk()
FILESYSTEM_DISK=public
```

### Php artisan
```sh
php artisan route:list
php artisan cache:clear
php artisan config:clear
php artisan key:generate
php artisan storage:link
php artisan session:table
php artisan queue:table
```

### Event listeners
```sh
php artisan make:listener PaymentCanceledNotification --event=PaymentCanceled
php artisan make:listener PaymentConfirmedNotification --event=PaymentConfirmed
```

## Tests

### Migration, seed
```sh
php artisan --env=testing migrate:fresh --seed

php artisan --env=testing db:seed --class="\Database\Seeders\PayDatabaseSeeder"
```

### Settings phpunit.xml
```xml
<testsuite name="Pay">
	<directory suffix="Test.php">./tests/Pay</directory>
</testsuite>
```

### Run tests
```sh
# Copy package test/Pay dir
php artisan vendor:publish --tag=pay-tests --force

# Only for config(['pay.payu.env' => 'sandbox'])
php artisan test --testsuite=Pay --stop-on-failure
```

## Composer

### Local directory
```json
{
	"repositories": [{
		"type": "path",
		"url": "packages/breakermind/pay"
	}],
	"require": {
		"breakermind/pay": "dev-main"
	}
}
```

### Remote git repo
```json
{
	"repositories": [{
		"type": "vcs",
		"url": "https://github.com/breakermind/pay"
	}],
	"require": {
		"breakermind/pay": "*"
	},
}
```

### Require
```sh
# cmd
composer require breakermind/pay "~1.0.0"

# composer.json
{
	"require": {
		"breakermind/pay": "~1.0.0"
	}
}
```

## Payment APIs

### Payu api sdk
https://github.com/PayU-EMEA/openpayu_php