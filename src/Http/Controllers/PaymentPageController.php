<?php
namespace Pay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;

/**
 * Controller example
 */
class PaymentPageController extends Controller
{
	function success(Order $order)
	{
		if(!empty(request()->input('error'))) {
			return $this->error($order);
		}

		return view('pay::page.success', ['order' => $order]);
	}

	function error(Order $order)
	{
		return view('pay::page.error', ['order' => $order, 'error' => request()->input('error')]);
	}
}