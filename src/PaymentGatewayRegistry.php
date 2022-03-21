<?php

namespace Pay;

use Exception;
use Pay\PaymentGateway;

class PaymentGatewayRegistry
{
	protected $gateways = [];

	function register ($name, PaymentGateway $instance)
	{
		$this->gateways[strtolower($name)] = $instance;
		return $this;
	}

	function get($name)
	{
		if (array_key_exists(strtolower($name), $this->gateways)) {
			return $this->gateways[strtolower($name)];
		} else {
			throw new Exception("Invalid gateway.");
		}
	}

	function getAll()
	{
		return $this->gateways;
	}
}