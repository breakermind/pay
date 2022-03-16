<?php

namespace Pay\Facades;

use Illuminate\Support\Facades\Facade;

class Pay extends Facade {
	protected static function getFacadeAccessor() {
		return 'pay-facade';
	}
}