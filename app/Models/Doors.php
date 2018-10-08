<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Doors
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class Doors extends Model
{
    /**
     * @var string
     */
    protected $table = 'doors';

    /**
     * @var bool
     */
    public $timestamps = false;
}
