<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class generic extends Model
{
    /**
     * Get's the current time of the server
     * 
     * @return int|bool timestamp as integer, false on failure
     */
    static function getServerTime(): int|bool
    {
        return strtotime(DB::select('SELECT NOW() AS now')[0]->now);
    }
}
