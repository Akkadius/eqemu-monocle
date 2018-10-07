<?php
/**
 * Created by PhpStorm.
 * User: akkadius
 * Date: 10/7/18
 * Time: 2:18 AM
 */

namespace App\Services;

use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
use Exception;

class DataDumpImportService
{

    /**
     * @var DataDumpReaderService
     */
    protected $data_dump_reader_service;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $import_type;

    /**
     * @var string
     */
    protected $zone_short_name;

    /**
     * @var int
     */
    protected $zone_instance_version;

    /**
     * DataDumpImportService constructor.
     * @param DataDumpReaderService $data_dump_reader_service
     */
    public function __construct(DataDumpReaderService $data_dump_reader_service)
    {
        $this->data_dump_reader_service = $data_dump_reader_service;
    }

    /**
     * Validate required parameters before execute
     * @throws Exception
     */
    protected function validate()
    {
        if (empty($this->zone_short_name)) {
            throw new Exception("Zone short name required for import service");
        }

        if (empty($this->zone_instance_version)) {
            throw new Exception("Zone instance version required for import service");
        }

        if (empty($this->import_type)) {
            throw new Exception("Import type needs to be set!");
        }

        if (empty($this->file)) {
            throw new Exception("Valid csv file required for import service");
        }
    }

    /**
     * @throws Exception
     */
    public function process()
    {
        $this->validate();

        switch ($this->getImportType()) {
            case "npc":
                $this->importNpcData();
                break;
            default:
                throw new Exception("Import type: '{$this->getImportType()}' not found!");
        }
    }

    /**
     * @throws \League\Csv\Exception
     */
    public function importNpcData()
    {
        /**
         * Setup reader service
         */
        $this->data_dump_reader_service
            ->setFile($this->getFile())
            ->initReader()
            ->parse();

        $count = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {

            /**
             * Create NPC
             */
            $npc                    = new NpcTypes;
            $npc->bodytype          = array_get($row, 'body_type');
            $npc->class             = array_get($row, 'get_class');
            $npc->drakkin_details   = array_get($row, 'm_actor_client_details');
            $npc->drakkin_heritage  = array_get($row, 'm_actor_client_heritage');
            $npc->drakkin_tattoo    = array_get($row, 'm_actor_client_tattoo');
            $npc->face              = array_get($row, 'm_actor_client_face_style');
            $npc->gender            = array_get($row, 'm_actor_client_gender');
            $npc->lastname          = array_get($row, 'lastname');
            $npc->level             = array_get($row, 'level');
            $npc->luclin_beard      = array_get($row, 'm_actor_client_facial_hair');
            $npc->luclin_beardcolor = array_get($row, 'm_actor_client_facial_hair_color');
            $npc->luclin_eyecolor   = array_get($row, 'm_actor_client_eye_color1');
            $npc->luclin_eyecolor2  = array_get($row, 'm_actor_client_eye_color2');
            $npc->luclin_haircolor  = array_get($row, 'm_actor_client_hair_color');
            $npc->luclin_hairstyle  = array_get($row, 'm_actor_client_hair_style');
            $npc->name              = array_get($row, 'name');
            $npc->race              = array_get($row, 'm_actor_client_race');
            $npc->size              = array_get($row, 'height');
            $npc->texture           = array_get($row, 'm_actor_client_texture_type');

            /**
             * See invis
             */
            $see_invis      = array_get($row, 'see_invis0');
            $npc->see_invis = ($see_invis ? 1 : 0);

            /**
             * Save
             */
            $npc->save();

            /**
             * Create Spawn Group
             */
            $spawn_group       = new SpawnGroup;
            $spawn_group->name = $this->getZoneShortName() . "_monocle_" . $count;
            $spawn_group->save();

            /**
             * Create Spawn Entry
             */
            $spawn_entry               = new SpawnEntry;
            $spawn_entry->npcID        = $npc->id;
            $spawn_entry->chance       = 100;
            $spawn_entry->spawngroupID = $spawn_group->id;
            $spawn_entry->save();

            /**
             * Create Spawn2
             */
            $spawn2               = new Spawn2;
            $spawn2->spawngroupID = $spawn_group->id;
            $spawn2->zone         = $this->getZoneShortName();
            $spawn2->version      = $this->getZoneInstanceVersion();
            $spawn2->x            = array_get($row, 'x');
            $spawn2->y            = array_get($row, 'y');
            $spawn2->z            = array_get($row, 'z');
            $spawn2->heading      = array_get($row, 'heading');
            $spawn2->save();

            $count++;
        }

        dump("Created {$count} NPC's in " . $this->getZoneShortName());
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param string $file
     * @return DataDumpImportService
     */
    public function setFile(string $file): DataDumpImportService
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getZoneShortName(): string
    {
        return $this->zone_short_name;
    }

    /**
     * @param string $zone_short_name
     * @return DataDumpImportService
     */
    public function setZoneShortName(string $zone_short_name): DataDumpImportService
    {
        $this->zone_short_name = $zone_short_name;

        return $this;
    }

    /**
     * @param string $import_type
     * @return DataDumpImportService
     */
    public function setImportType(string $import_type): DataDumpImportService
    {
        $this->import_type = $import_type;

        return $this;
    }

    /**
     * @return string
     */
    public function getImportType(): string
    {
        return $this->import_type;
    }

    /**
     * @param int $zone_instance_version
     * @return DataDumpImportService
     */
    public function setZoneInstanceVersion(int $zone_instance_version): DataDumpImportService
    {
        $this->zone_instance_version = $zone_instance_version;

        return $this;
    }

    /**
     * @return int
     */
    public function getZoneInstanceVersion(): int
    {
        return $this->zone_instance_version;
    }
}