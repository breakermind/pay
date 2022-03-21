<?php

namespace Pay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\ClientFactory;
use App\Models\Order;

class Client extends Model
{
	use HasFactory, SoftDeletes;

	public function order()
	{
		// 'foreign_key', 'owner_key'
		return $this->belongsTo(Order::class, 'order_id', 'id');
	}

	protected static function newFactory()
	{
		return ClientFactory::new();
	}
}
