<?php

namespace Pay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\OrderFactory;
use Pay\Models\Traits\Uuids;

/**
 *  Order with uuid primary key
 */
class UuidOrder extends Model
{
	use HasFactory, SoftDeletes, Uuids;

	protected $primaryKey = 'uid';

	protected $table = 'orders';

	protected $guarded = [];

	protected static function newFactory()
	{
		return OrderFactory::new();
	}
}
