<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class competition extends Model
{
    // tell lumen the model has an assigned factory
    use HasFactory;

    // no need to talk about primary id since it is exactly what eloquent assumes

    /**
     * @var array The model's default values for attributes.
     */
    protected $attributes = [];


    public $timestamps = false;

    /**
     * @var array The attributes that are protected from  mass assigning.
     */
    protected $guarded = ['changed', 'id', 'user_id'];

    /**
     * @var array The attributes that are hidden from api output
     */
    protected $hidden = ['user_id'];

    /**
     * @var array The attributes that should be cast.
     */
    protected $casts = [
        'changed' => 'timestamp',
        'date' => 'timestamp',
        'live' => 'boolean'
    ];
}
