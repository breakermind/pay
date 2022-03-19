<?php
namespace Pay\Http\Controllers;

use App\Http\Controllers\Controller;
use Pay\PaymentGatewayRegistry;
use App\Models\Order;

class PaymentController extends Controller
{
	function __construct (PaymentGatewayRegistry $registry)
	{
		$this->gatewayRegistry = $registry;
	}

	function notify($gateway)
	{
		return $this->gatewayRegistry->get($gateway)->notify();
	}

	function pay($gateway, Order $order)
	{
		return response()->json([
			'url' => $this->gatewayRegistry->get($gateway)->pay($order)
		]);
	}

	function confirm($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->confirm($order)
		]);
	}

	function cancel($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->cancel($order)
		]);
	}

	function refresh($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->refresh($order)
		]);
	}

	function retrive($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->retrive($order)
		]);
	}

	function transaction($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->transaction($order)
		]);
	}

	function refund($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->refund($order)
		]);
	}

	function refunds($gateway, Order $order)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->refunds($order)
		]);
	}

	function payments($gateway, $lang)
	{
		return response()->json([
			'message' => $this->gatewayRegistry->get($gateway)->payments($lang)
		]);
	}
}