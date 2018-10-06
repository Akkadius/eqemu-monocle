<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SpawnGroup
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class SpawnGroup extends Model
{
    /**
     * @var string
     */
    protected $table = 'spawngroup';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get Spawn2
     */
    public function spawn2()
    {
        return $this->hasMany('App\Models\Spawn2', 'spawngroupID', 'id');
    }
}
