<?php

namespace App\Console\Commands;

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
        {delete_type : npc|door|object|groundspawn|zonepoint|zone|all}
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
     * @param ZoneDataDeleteService $zone_data_delete_service
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

        /**
         * Setup data deletion service
         */
        $zone_data_delete_service
            ->setZoneShortName($zone_short_name)
            ->setZoneInstanceVersion($zone_instance_version);

        switch ($delete_type) {
            case "npc":
                $zone_data_delete_service->deleteNpcData();
                break;
            case "door":
                $zone_data_delete_service->deleteDoorData();
                break;
            case "groundspawn":
                $zone_data_delete_service->deleteGroundSpawnData();
                break;
            case "zonepoint":
                $zone_data_delete_service->deleteZonePointData();
                break;
            case "all":
                $zone_data_delete_service->deleteAll();
                break;
        }

        $this->info($zone_data_delete_service->getProcessMessages());
    }
}
