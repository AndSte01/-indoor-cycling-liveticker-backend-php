<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class competition extends Model
{
    // tell lumen the model has an assigned factory
    use HasFactory;

    // no need to talk about primary id since it is exactly what eloquent assumes

    /**
     * @var array The model's default values for attributes.
     */
    protected $attributes = [];

    // disable timestamps
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
     * Set of attributes in the model, excluding it's primary id that fully describe it
     */
    // protected $implicitIdentifiers = [['user', 'foreign'], 'name', 'location'];

    /**
     * @var array The attributes that should be cast.
     */
    protected $casts = [
        'changed' => 'datetime',
        'date_start' => 'date',
        'date_end' => 'date',
        'feature_set' => 'integer',
        'areas' => 'integer',
        'live' => 'boolean'
    ];

    /**
     * Get the discipline(s) assigned to the competition
     */
    public function discipline(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return int
     */
    protected function serializeDate(DateTimeInterface $date): int
    {
        return intval($date->format('U'));
    }

    /**
     * Check whether the given user has access to the desired resource or not
     */
    public function checkAccess(int $user): bool
    {
        return ($this->user_id == $user);
    }
}
