<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZonePoint
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class ZonePoint extends Model
{
    /**
     * @var string
     */
    protected $table = 'zone_points';

    /**
     * @var bool
     */
    public $timestamps = false;
}
