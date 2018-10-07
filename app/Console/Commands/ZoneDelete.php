<?php

namespace App\Console\Commands;

use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
use App\Services\ZoneDataDeleteService;
use Illuminate\Console\Command;

class ZoneDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zonetools:zone-delete 
        {zone_short_name} 
        {zone_instance_version}
        {delete_type : npc|door|object|grounditem|zonepoints|zone|all}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes entity data in a zone';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(ZoneDataDeleteService $zone_data_delete_service)
    {
        /**
         * Get args
         */
        $zone_short_name       = $this->argument('zone_short_name');
        $zone_instance_version = $this->argument('zone_instance_version');
        $delete_type           = $this->argument('delete_type');

        $zone_data_delete_service
            ->setZoneShortName($zone_short_name)
            ->setZoneInstanceVersion($zone_instance_version);

        if ($delete_type == "npc") {
            $zone_data_delete_service->deleteNpcData();
        }

        if ($delete_type == "all") {
            $zone_data_delete_service->deleteAll();
        }

        $this->info($zone_data_delete_service->getProcessMessages());
    }
}
