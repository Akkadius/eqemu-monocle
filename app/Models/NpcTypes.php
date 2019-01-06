<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class NpcTypes
 *
 * @package App\Models
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SpawnEntry[] $spawnentries
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
