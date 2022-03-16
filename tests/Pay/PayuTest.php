<?php

namespace Tests\Pay;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use Database\Seeders\PayDatabaseSeeder;

/**
 * php artisan --env=testing migrate:fresh --seed
 * php artisan --env=testing db:seed --class="\Database\Seeders\PayDatabaseSeeder"
 *
 * File: phpunit.xml
 * <testsuite name="Pay">
 *	<directory suffix="Test.php">./tests/Pay</directory>
 * </testsuite>
 *
 * php artisan vendor:publish --tag=pay-tests
 * php artisan test --testsuite=Pay --stop-on-failure
 */
class PayuTest extends TestCase
{
	use RefreshDatabase;

	/** @test */
	public function sandbox_config()
	{
		if(config('pay.payu.env') == 'sandbox') {
			$this->assertNotEmpty(config('pay.payu.env'));
			$this->assertNotEmpty(config('pay.payu.pos_id'));
			$this->assertNotEmpty(config('pay.payu.pos_md5'));
			$this->assertNotEmpty(config('pay.payu.client_id'));
			$this->assertNotEmpty(config('pay.payu.client_secret'));
		}

		$this->assertTrue(true);
	}

	/** @test */
	public function sandbox_payment_url()
	{
		if(config('pay.payu.env') == 'sandbox') {
			// Create demo orders
			$this->seed(PayDatabaseSeeder::class);

			// Get first order
			$o = Order::first();
			$this->assertNotEmpty($o->uid);

			// Create payment url
			$res = $this->get('/web/payment/url/payu/'.$o->uid);
			$res->assertStatus(200);
			$this->assertNotEmpty($res['url']);

			// Test payment url
			$res = $this->get($res['url']);
			$res->assertStatus(200);

			// Test payu payment page content
			$res->assertSeeText($o->payment->email);
		}

		$this->assertTrue(true);
	}
}
