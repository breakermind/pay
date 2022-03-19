<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use Pay\Http\Controllers\PaymentController;
use Pay\Http\Controllers\PaymentPageController;

Route::prefix('/web')->name('web.')->middleware(['web', 'pay'])->group(function () {

	if (config('pay.payu.env') == 'sandbox') {
		Route::get('/payment/url/{gateway}/{order}', [PaymentController::class, 'pay'])
			->name('pay.url');

		Route::get('/payment/confirm/{gateway}/{order}', [PaymentController::class, 'confirm'])
			->name('pay.confirm');

		Route::get('/payment/cancel/{gateway}/{order}', [PaymentController::class, 'cancel'])
			->name('pay.cancel');

		Route::get('/payment/refresh/{gateway}/{order}', [PaymentController::class, 'refresh'])
			->name('pay.refresh');

		Route::get('/payment/retrive/{gateway}/{order}', [PaymentController::class, 'retrive'])
			->name('pay.retrive');

		Route::get('/payment/transaction/{gateway}/{order}', [PaymentController::class, 'transaction'])
			->name('pay.transaction');

		Route::get('/payment/refund/{gateway}/{order}', [PaymentController::class, 'refund'])
			->name('pay.refund');

		Route::get('/payment/refunds/{gateway}/{order}', [PaymentController::class, 'refunds'])
			->name('pay.refunds');

		Route::get('/payment/payments/{gateway}/{lang}', [PaymentController::class, 'payments'])
			->name('pay.payments');
	}

	Route::get('/payment/success/{order}', [PaymentPageController::class, 'success'])
		->withoutMiddleware([VerifyCsrfToken::class])
		->name('pay.success');

	Route::post('/payment/notify/{gateway}', [PaymentController::class, 'notify'])
		->withoutMiddleware([VerifyCsrfToken::class])
		->name('pay.notify');
});
