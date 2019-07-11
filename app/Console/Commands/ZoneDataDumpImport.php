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
        {--s|skip-confirmation : Skips file prompt confirmation}
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
        $skip_confirmation     = $this->option('skip-confirmation');

        /**
         * One-off type transform to match file name
         */
        if ($dump_type == "groundspawn") {
            $dump_type = "grounditem";
        }

        if ($dump_type == "object") {
            $dump_type = "objects";
        }

        /**
         * Loop through local files
         */
        $files          = Storage::files('.');
        $file_to_read   = "";
        $file_timestamp = "";

        $imports_to_run = [$dump_type];

        if ($dump_type == "all") {
            $imports_to_run = [
                "zone",
                "npc",
                "door",
                "grounditem",
                "objects",
                "zonepoint"
            ];
        }

        foreach ($imports_to_run as $import_type) {

            foreach ($files as $file_raw) {
                $file                      = strtolower($file_raw);
                $file_clean                = str_replace(".csv", "", $file);
                $file_parameters           = explode("_", $file_clean);
                $zone_short_name_parameter = array_get($file_parameters, 0, '');

                /**
                 * Some of our imports set the type not exactly in the same position, so lets dynamically find it in
                 * the filename
                 *
                 * Example: thundercrest_70_Door_2019-03-30-18-54-52.csv
                 */
                $dump_type_index = 0;
                foreach ($file_parameters as $file_parameter) {
                    if ($file_parameter == $import_type) {
                        break;
                    }
                    $dump_type_index++;
                }

                $dump_type_parameter       = array_get($file_parameters, $dump_type_index, '');
                $time_stamp_parameter      = array_get($file_parameters, $dump_type_index + 1, '');

                if ($zone_short_name == $zone_short_name_parameter && $import_type == $dump_type_parameter) {
                    if ($skip_confirmation) {
                        $file_to_read   = $file;
                        $file_timestamp = $time_stamp_parameter;
                        break;
                    }

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
                ->setFile($file_raw)
                ->setZoneShortName($zone_short_name)
                ->setZoneInstanceVersion($zone_instance_version);

            switch ($import_type) {
                case "npc":
                    $zone_data_dump_import_service->importNpcData();
                    break;
                case "door":
                    $zone_data_dump_import_service->importDoorData();
                    break;
                case "grounditem":
                    $zone_data_dump_import_service->importGroundSpawnData();
                    break;
                case "objects":
                    $zone_data_dump_import_service->importObjectData();
                    break;
                case "zonepoint":
                    $zone_data_dump_import_service->importZonePointData();
                    break;
                case "zone":
                    $zone_data_dump_import_service->importZoneHeaderData();
                    break;
                case "all":
                    $zone_data_dump_import_service->importAll();
                    break;
            }
        }

        foreach ($zone_data_dump_import_service->getCreated() as $created_type => $count) {
            $this->info("Created ({$count}) {$created_type}");
        }
    }
}
