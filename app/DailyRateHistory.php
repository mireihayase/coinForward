<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyRateHistory extends Model {

	use SoftDeletes;

	public $timestamps = false;
}
