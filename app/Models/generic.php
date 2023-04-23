<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class generic extends Model
{
    /**
     * Get's the current time of the server
     * 
     * @return Carbon Carbon Object containing the current time of the SQL server
     */
    static function getServerTime(): Carbon
    {
        return Carbon::parse(DB::select('SELECT NOW() AS now')[0]->now);
    }
}
