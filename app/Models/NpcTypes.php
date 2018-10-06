<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class NpcTypes
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class NpcTypes extends Model
{
    /**
     * @var string
     */
    protected $table = 'npc_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get Spawnentry
     */
    public function spawnentries()
    {
        return $this->hasMany('App\Models\SpawnEntry', 'npcID');
    }
}
