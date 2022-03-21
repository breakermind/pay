<?php

namespace Pay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Database\Factories\OrderFactory;
use App\Models\User;
use App\Models\Client;
use Pay\Models\Payment;

class Order extends Model
{
	use HasFactory, SoftDeletes;

	protected $guarded = [];

	public function client()
	{
		return $this->hasOne(Client::class, 'order_id', 'id');
	}

	public function payment()
	{
		return $this->hasOne(Payment::class, 'order_uid', 'uid');
	}

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($model) {
			if (empty($model->uid)) {
				$model->uid = (string) Str::uuid();
			}
		});
	}

	protected static function newFactory()
	{
		return OrderFactory::new();
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format('Y-m-d H:i:s');
	}
}
