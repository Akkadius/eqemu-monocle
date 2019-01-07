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

    /**
     * @param string $zone_short_name
     * @return int|null
     */
    public static function getZoneIdByShortName(string $zone_short_name): ?int
    {
        return Zone::where('short_name', $zone_short_name)
            ->first()
            ->zoneidnumber;
    }
}
