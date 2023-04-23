<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contains a function to determine the time of the MySQL server
 */
class ServerTimeHelper
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
