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

	protected $guarded = [];

	protected $dateFormat = 'Y-m-d H:i:s';

	public function order()
	{
		// 'foreign_key', 'owner_key'
		return $this->belongsTo(Order::class, 'order_id', 'id');
	}

	protected static function newFactory()
	{
		return ClientFactory::new();
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format($this->dateFormat);
	}
}
