<?php

namespace Pay;

use Exception;
use App\Models\Order;
use Pay\PaymentGatewayRegistry;

class Pay
{
	protected $url;

	function __construct (PaymentGatewayRegistry $registry)
	{
		$this->gatewayRegistry = $registry;
	}

	public function pay($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->pay($order);
	}

	function confirm($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->confirm($order);
	}

	function cancel($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->cancel($order);
	}

	function refresh($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->refresh($order);
	}

	function retrive($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->retrive($order);
	}

	function transaction($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->transaction($order);
	}

	function refund($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->refund($order);
	}

	function refunds($gateway, Order $order)
	{
		return $this->gatewayRegistry->get($gateway)->refunds($order);
	}

	function notify($gateway)
	{
		return $this->gatewayRegistry->get($gateway)->notify();
	}

	function logo() {
		return $this->gatewayRegistry->get($gateway)->logo();
	}
}