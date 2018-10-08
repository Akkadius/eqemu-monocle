<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Door
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class Door extends Model
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
