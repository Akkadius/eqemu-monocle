<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Zone
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class Zone extends Model
{
    /**
     * @var string
     */
    protected $table = 'zone';

    /**
     * @var bool
     */
    public $timestamps = false;
}
