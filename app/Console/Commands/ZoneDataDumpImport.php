<?php

namespace App\Console\Commands;

use App\Services\ZoneDataDumpImportService;
use App\Services\ZoneDataDumpReaderService;
use Illuminate\Console\Command;
use Storage;

/**
 * Class ZoneDataDumpImport
 * @package App\Console\Commands
 */
class ZoneDataDumpImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zonetools:dump-import
        {zone_short_name}
        {zone_instance_version}
        {dump_type : npc|door|object|groundspawn|zonepoint|zone|all}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parses CSV zone dumps';

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
     * @param ZoneDataDumpReaderService $data_dump_reader_service
     * @return mixed
     * @throws \League\Csv\Exception
     * @throws \Exception
     */
    public function handle(ZoneDataDumpImportService $zone_data_dump_import_service)
    {
        /**
         * Get args
         */
        $zone_short_name       = $this->argument('zone_short_name');
        $zone_instance_version = $this->argument('zone_instance_version');
        $dump_type             = $this->argument('dump_type');

        if ($dump_type == "groundspawn") {
            $dump_type = "grounditem";
        }

        /**
         * Loop through local files
         */
        $files          = Storage::files('.');
        $file_to_read   = "";
        $file_timestamp = "";

        foreach ($files as $file) {
            $file                      = strtolower($file);
            $file_clean                = str_replace(".csv", "", $file);
            $file_parameters           = explode("_", $file_clean);
            $zone_short_name_parameter = array_get($file_parameters, 0, '');
            $dump_type_parameter       = array_get($file_parameters, 1, '');
            $time_stamp_parameter      = array_get($file_parameters, 2, '');

            if ($zone_short_name == $zone_short_name_parameter && $dump_type == $dump_type_parameter) {
                if ($this->confirm("Use this file? {$file}")) {
                    $file_to_read   = $file;
                    $file_timestamp = $time_stamp_parameter;
                    break;
                }
            }
        }

        if (empty($file_to_read)) {
            $this->error("No file to read!");
            exit;
        }

        $this->info("Reading file '{$file}' time: {$file_timestamp}");

        /**
         * Process import
         */
        $zone_data_dump_import_service
            ->setFile($file)
            ->setZoneShortName($zone_short_name)
            ->setZoneInstanceVersion($zone_instance_version);

        switch ($dump_type) {
            case "npc":
                $zone_data_dump_import_service->importNpcData();
                break;
            case "door":
                $zone_data_dump_import_service->importDoorData();
                break;
            case "grounditem":
                $zone_data_dump_import_service->importGroundSpawnData();
                break;
            case "zonepoint":
                $zone_data_dump_import_service->importZonePointData();
                break;
            case "all":
                $zone_data_dump_import_service->importAll();
                break;
        }
    }
}