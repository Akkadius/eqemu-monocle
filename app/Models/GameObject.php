<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GameObject
 *
 * @package App\Models
 * @mixin \Eloquent
 */
class GameObject extends Model
{
    /**
     * @var string
     */
    protected $table = 'object';

    /**
     * @var bool
     */
    public $timestamps = false;
}
