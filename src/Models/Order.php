<?php

namespace Pay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\OrderFactory;
use Pay\Models\Traits\Uuids;
use App\Models\Client;
use Pay\Models\Payment;

// Order sample
class Order extends Model
{
	use HasFactory, SoftDeletes, Uuids;

	protected $primaryKey = 'uid';

	protected $dateFormat = 'Y-m-d H:i:s';

	protected $guarded = [];

	public function client()
	{
		// 'foreign_key', 'local_key'
		return $this->hasOne(Client::class, 'order_uid', 'uid');
	}

	public function payment()
	{
		// 'foreign_key', 'local_key'
		return $this->hasOne(Payment::class, 'order_uid', 'uid');
	}

	protected static function newFactory()
	{
		return OrderFactory::new();
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format($this->getDateFormat());
	}
}
