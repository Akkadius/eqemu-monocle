<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroundSpawn
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class GroundSpawn extends Model
{
    /**
     * @var string
     */
    protected $table = 'ground_spawns';

    /**
     * @var bool
     */
    public $timestamps = false;
}
