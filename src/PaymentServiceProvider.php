<?php

namespace Pay;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Pay\PaymentGatewayRegistry;
use Pay\Gateways\PayuPaymentGateway;
use Pay\Http\Middleware\PaymentMiddleware;

class PaymentServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['router']->aliasMiddleware('pay', PaymentMiddleware::class);
		$this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'pay');

		// Register gateway registery object
		$this->app->singleton(PaymentGatewayRegistry::class);

		// Facade
		$this->app->bind('pay-facade', function($app) {
			return new Pay($this->app->make(PaymentGatewayRegistry::class));
		});

		// Register PayU gateway instance
		if (config('pay.enable.payu') == true) {
			$this->app->make(PaymentGatewayRegistry::class)->register("payu", new PayuPaymentGateway());
		}

		// Register PayPal gateway instance
		// if (config('pay.enable.paypal') == true) {
		// 	$this->app->make(PaymentGatewayRegistry::class)->register("paypal", new PayPalPaymentGateway());
		// }

		// Add more gateways here ...
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'pay');
		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'pay');

		if (config('pay.migrations') == true) {
			$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
		}

		if (config('pay.routes') == true) {
			$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
		}

		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__ . '/../config/config.php' => config_path('pay.php'),
				__DIR__.'/../public' => public_path('vendor/pay'),
			], 'pay-config');

			$this->publishes([
				__DIR__ . '/../resources/views' => resource_path('views/vendor/pay'),
				__DIR__.'/../resources/lang' => $this->app->langPath('vendor/pay'),
			], 'pay-pages');

			$this->publishes([
				__DIR__.'/../database/migrations' => database_path('/migrations'),
			], 'pay-migrations');

			$this->publishes([
				__DIR__.'/../public' => public_path('vendor/pay'),
			], 'pay-public');

			$this->publishes([
				__DIR__ . '/../tests/Pay' => base_path('tests/Pay')
			], 'pay-tests');
		}
	}

	/**
	 * Bind interfaces example
	 *
	 * https://laravel.com/docs/9.x/providers
	 * https://laravel.com/docs/9.x/container
	 * https://laravel.com/docs/9.x/container#binding
	 *
	 * @return void
	 */
	public function bindPayInterface()
	{
		// Bind
		// $this->app->when(Pay::class)
		// 	->needs(PaymentGatewayRegistry::class)
		// 	->give(function ($app) {
		// 		return [
		// 			$app->make(PaymentGatewayRegistry::class),
		// 		];
		// 	});

		// $this->app->bind(PayInterface::class, function($app) {
		// 	// Config
		// 	if($app['config']->has('pay.config.var')) {
		// 		$this->someVar = $app['config']->get('pay.config.var');
		// 		$this->someVar = config('pay.config.var');
		// 	}

		// 	// Bindings
		// 	if($this->app->request->route('gateway') == 'payu') {
		// 		return new OnePayInstance($app);
		// 	} else {
		// 		return new TwoPayInstance($app);
		// 	}
		// });
	}
}
