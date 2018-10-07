<?php

namespace App\Console\Commands;

use App\Services\DataDumpImportService;
use App\Services\DataDumpReaderService;
use Illuminate\Console\Command;
use Storage;

/**
 * Class ZoneTools
 * @package App\Console\Commands
 */
class ZoneTools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zonetools:dump-import
        {zone_short_name}
        {zone_instance_version}
        {dump_type}
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
     * @param DataDumpReaderService $data_dump_reader_service
     * @return mixed
     * @throws \League\Csv\Exception
     * @throws \Exception
     */
    public function handle(DataDumpImportService $data_dump_import_service)
    {
        /**
         * Get args
         */
        $zone_short_name       = $this->argument('zone_short_name');
        $zone_instance_version = $this->argument('zone_instance_version');
        $dump_type             = $this->argument('dump_type');

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
        $data_dump_import_service
            ->setFile($file)
            ->setImportType($dump_type)
            ->setZoneShortName($zone_short_name)
            ->setZoneInstanceVersion($zone_instance_version)
            ->process();
    }
}