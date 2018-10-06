<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SpawnEntry
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class SpawnEntry extends Model
{
    /**
     * @var string
     */
    protected $table = 'spawnentry';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'spawngroupID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['*'];

    /**
     * Get SpawnGroup
     */
    public function spawngroup()
    {
        return $this->hasOne('App\Models\SpawnGroup', 'id');
    }
}
