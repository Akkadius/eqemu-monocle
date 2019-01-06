<?php
/**
 * Created by PhpStorm.
 * User: akkadius
 * Date: 10/7/18
 * Time: 2:18 AM
 */

namespace App\Services;

use App\Models\Door;
use App\Models\GameObject;
use App\Models\GroundSpawn;
use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
use App\Models\Zone;
use App\Models\ZonePoint;
use Exception;

class ZoneDataDumpImportService
{
    const ENTITY_TYPE_PLAYER = 0;
    const ENTITY_TYPE_CORPSE = 2;

    /**
     * @var ZoneDataDumpReaderService
     */
    protected $data_dump_reader_service;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $zone_short_name;

    /**
     * @var int
     */
    protected $zone_instance_version;

    /**
     * @var array
     */
    protected $created_count = [];

    /**
     * DataDumpImportService constructor.
     * @param ZoneDataDumpReaderService $data_dump_reader_service
     */
    public function __construct(ZoneDataDumpReaderService $data_dump_reader_service)
    {
        $this->data_dump_reader_service = $data_dump_reader_service;
    }

    /**
     * Validate required parameters before execute
     * @return ZoneDataDumpImportService
     * @throws Exception
     */
    protected function validate(): ZoneDataDumpImportService
    {
        if (empty($this->zone_short_name)) {
            throw new Exception("Zone short name required for import service");
        }

        if (empty($this->zone_instance_version) && $this->zone_instance_version != 0) {
            throw new Exception("Zone instance version required for import service");
        }

        if (empty($this->file)) {
            throw new Exception("Valid csv file required for import service");
        }

        return $this;
    }

    /**
     * @return ZoneDataDumpImportService
     * @throws \League\Csv\Exception
     */
    public function readerParse(): ZoneDataDumpImportService
    {
        $this->data_dump_reader_service
            ->setFile($this->getFile())
            ->initReader()
            ->parse();

        return $this;
    }

    /**
     * @return ZoneDataDumpImportService
     * @throws \League\Csv\Exception
     */
    public function importAll(): ZoneDataDumpImportService
    {
        $this
            ->importDoorData()
            ->importGroundSpawnData()
            ->importNpcData()
            ->importObjectData()
            ->importZoneHeaderData()
            ->importZonePointData();

        return $this;
    }

    /**
     * @return $this
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importNpcData()
    {
        $this->validate()->readerParse();

        $count = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {

            $last_name      = array_get($row, 'lastname');
            $displayed_name = array_get($row, 'displayed_name');
            $entity_type    = array_get($row, 'type');

            if (strpos($last_name, "'s Mercenary") !== false) {
                continue;
            }

            if (strpos($last_name, "'s Pet") !== false) {
                continue;
            }

            if (strpos($displayed_name, "`s Mount") !== false) {
                continue;
            }

            if ($entity_type == self::ENTITY_TYPE_CORPSE) {
                continue;
            }

            if ($entity_type == self::ENTITY_TYPE_PLAYER) {
                continue;
            }

            /**
             * Strip numbers from name
             */
            $npc_name = preg_replace('/\d/', '', array_get($row, 'name'));

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
            $npc->name              = $npc_name;
            $npc->race              = array_get($row, 'm_actor_client_race');
            $npc->size              = array_get($row, 'height');
            $npc->texture           = array_get($row, 'equipment_chestid');
            $npc->d_melee_texture1  = array_get($row, 'equipment_primaryid');
            $npc->d_melee_texture2  = array_get($row, 'equipment_offhandid');

            /**
             * Stats
             */
            $npc->STA  = 0;
            $npc->STR  = 0;
            $npc->DEX  = 0;
            $npc->AGI  = 0;
            $npc->_INT = 0;
            $npc->WIS  = 0;
            $npc->CHA  = 0;

            /**
             * Resists
             */
            $npc->MR     = 0;
            $npc->CR     = 0;
            $npc->FR     = 0;
            $npc->DR     = 0;
            $npc->PR     = 0;
            $npc->Corrup = 0;
            $npc->PhR    = 0;

            /**
             * Misc
             */
            $npc->attack_delay = 0;
            $npc->runspeed     = 1.325;

            /**
             * Targetable
             */
            $target_able       = array_get($row, 'targetable', false);
            $npc->untargetable = ($target_able ? 0 : 1);
            if (!$target_able) {
                $npc->bodytype = 11;
            }

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

        $this->setCreatedCount("npc_types", $count);

        return $this;
    }

    /**
     * @return $this
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importDoorData()
    {
        $this->validate()->readerParse();

        $count = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {

            // TODO: Verify below
            // Destination we fill out ourselves I believe
            // dest_zone
            // dest_instance
            // dest_x
            // dest_y
            // dest_z
            // dest_heading
            // invert_state
            // incline
            // buffer
            // client_version_mask
            // is_ldon_door

            $door           = new Door;
            $door->doorid   = array_get($row, 'id');
            $door->zone     = $this->getZoneShortName();
            $door->version  = $this->getZoneInstanceVersion();
            $door->name     = array_get($row, 'name');
            $door->pos_x    = array_get($row, 'default_x');
            $door->pos_y    = array_get($row, 'default_y');
            $door->pos_z    = array_get($row, 'default_z');
            $door->heading  = array_get($row, 'default_heading');
            $door->opentype = array_get($row, 'type');
            $door->size     = array_get($row, 'scale_factor');
            $door->keyitem  = (array_get($row, 'key') != -1 ? array_get($row, 'key') : 0);
            $door->save();

            $count++;
        }

        $this->setCreatedCount("doors", $count);

        return $this;
    }

    /**
     * @return $this
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importZonePointData()
    {
        $this->validate()->readerParse();

        $count = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {

            $zone_point                 = new ZonePoint;
            $zone_point->zone           = $this->getZoneShortName();
            $zone_point->version        = $this->getZoneInstanceVersion();
            $zone_point->x              = array_get($row, 'x');
            $zone_point->y              = array_get($row, 'y');
            $zone_point->z              = array_get($row, 'z');
            $zone_point->target_zone_id = array_get($row, 'target_zone_id');
            $zone_point->number         = array_get($row, 'index');

            /**
             * TODO: Missing target x/y/z
             */

            $zone_point->save();

            $count++;
        }

        $this->setCreatedCount("zonepoints", $count);

        return $this;
    }

    /**
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importGroundSpawnData()
    {
        $this->validate()->readerParse();

        $zone_id = $this->getZoneIdByShortName($this->getZoneShortName());
        $count   = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {
            $ground_spawn          = new GroundSpawn;
            $ground_spawn->zoneid  = $zone_id;
            $ground_spawn->version = $this->getZoneInstanceVersion();
            $ground_spawn->min_x   = array_get($row, 'x');
            $ground_spawn->max_x   = array_get($row, 'x');
            $ground_spawn->min_y   = array_get($row, 'y');
            $ground_spawn->max_y   = array_get($row, 'y');
            $ground_spawn->max_z   = array_get($row, 'z');
            $ground_spawn->heading = array_get($row, 'heading');
            $ground_spawn->name    = array_get($row, 'name');
            $ground_spawn->comment = "Imported via monocole";
            $ground_spawn->save();

            $count++;
        }

        $this->setCreatedCount("ground_spawns", $count);

        return $this;
    }

    /**
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importObjectData()
    {
        $this->validate()->readerParse();

        /**
         * Fetch pre-existing types
         */
        $pre_existing_types = GameObject::distinct()
            ->get(['objectname', 'type', 'icon'])
            ->where('type', '<', 255)
            ->where('icon', '>', 0);

        $object_types = [];
        foreach ($pre_existing_types as $type) {
            $object_types[$type->objectname]['type'] = $type->type;
            $object_types[$type->objectname]['icon'] = $type->icon;
        }

        $zone_id = $this->getZoneIdByShortName($this->getZoneShortName());
        $count   = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {
            $object             = new GameObject;
            $object->zoneid     = $zone_id;
            $object->version    = $this->getZoneInstanceVersion();
            $object->xpos       = array_get($row, 'x');
            $object->ypos       = array_get($row, 'y');
            $object->zpos       = array_get($row, 'z');
            $object->heading    = array_get($row, 'heading');
            $object->objectname = array_get($row, 'name');
            $object->tilt_x     = array_get($row, 'roll');
            $object->tilt_y     = array_get($row, 'pitch');
            $object->size       = (array_get($row, 'scale', 1) * 100);
            $object->type       = array_get($object_types, $object->objectname . '.type', 255);
            $object->icon       = array_get($object_types, $object->objectname . '.icon', 0);
            $object->save();

            $count++;
        }

        $this->setCreatedCount("objects", $count);

        return $this;
    }

    /**
     * @param string $argb_int
     * @return array|null
     */
    private function argbIntToRgb(string $argb_int): ?array
    {
        $rgb['alpha'] = $argb_int >> 24 & 255;
        $rgb['red']   = $argb_int >> 16 & 255;
        $rgb['green'] = $argb_int >> 8 & 255;
        $rgb['blue']  = $argb_int & 255;

        return $rgb;
    }

    /**
     * @throws \League\Csv\Exception
     * @throws Exception
     */
    public function importZoneHeaderData()
    {
        $this->validate()->readerParse();

        $count = 0;
        foreach ($this->data_dump_reader_service->getCsvData() as $row) {

            /**
             * Calculate and split out ARGB values...
             */
            $fog_red   = array_get($row, 'fog_red');
            $fog_blue  = array_get($row, 'fog_blue');
            $fog_green = array_get($row, 'fog_green');
            $fog_red   = array_get($this->argbIntToRgb($fog_red), 'red', 0);
            $fog_blue  = array_get($this->argbIntToRgb($fog_blue), 'blue', 0);
            $fog_green = array_get($this->argbIntToRgb($fog_green), 'green', 0);

            /**
             * Fill in zone model
             */
            $zone                 = new Zone;
            $zone->short_name     = array_get($row, 'short_name');
            $zone->long_name      = array_get($row, 'long_name');
            $zone->zoneidnumber   = array_get($row, 'zone_id');
            $zone->version        = $this->getZoneInstanceVersion();
            $zone->minclip        = array_get($row, 'min_clip');
            $zone->maxclip        = array_get($row, 'max_clip');
            $zone->fog_red        = $fog_red;
            $zone->fog_blue       = $fog_blue;
            $zone->fog_green      = $fog_green;
            $zone->fog_minclip    = array_get($row, 'fog_start0');
            $zone->fog_density    = array_get($row, 'fog_density');
            $zone->gravity        = array_get($row, 'zone_gravity');
            $zone->rain_chance1   = array_get($row, 'rain_chance0');
            $zone->rain_chance2   = array_get($row, 'rain_chance1');
            $zone->rain_chance3   = array_get($row, 'rain_chance2');
            $zone->rain_chance4   = array_get($row, 'rain_chance3');
            $zone->rain_duration1 = array_get($row, 'rain_duration0');
            $zone->rain_duration2 = array_get($row, 'rain_duration1');
            $zone->rain_duration3 = array_get($row, 'rain_duration2');
            $zone->rain_duration4 = array_get($row, 'rain_duration3');
            $zone->snow_chance1   = array_get($row, 'snow_chance0');
            $zone->snow_chance2   = array_get($row, 'snow_chance1');
            $zone->snow_chance3   = array_get($row, 'snow_chance2');
            $zone->snow_chance4   = array_get($row, 'snow_chance3');
            $zone->snow_duration1 = array_get($row, 'snow_duration0');
            $zone->snow_duration2 = array_get($row, 'snow_duration1');
            $zone->snow_duration3 = array_get($row, 'snow_duration2');
            $zone->snow_duration4 = array_get($row, 'snow_duration3');

            /**
             * Yes - these are flipped
             */
            $zone->safe_x         = array_get($row, 'safe_y_loc');
            $zone->safe_y         = array_get($row, 'safe_x_loc');
            $zone->safe_z         = array_get($row, 'safe_z_loc');
            $zone->sky            = array_get($row, 'sky_type');
            $zone->underworld     = array_get($row, 'floor');
            $zone->ztype          = array_get($row, 'out_door');

            /**
             * TODO: Implement safe heading
             * Not using 'ceiling' value to counter 'floor'
             * $zone->safe_heading = array_get($row, 'safe_heading');
             */

            /**
             * Can levitate
             */
            $no_levitate       = array_get($row, 'b_no_levitate', true);
            $zone->canlevitate = ($no_levitate ? 0 : 1);

            /**
             * Buff Expiration
             */
            $no_buff_expiration = array_get($row, 'b_no_buff_expiration', true);
            $zone->suspendbuffs = ($no_buff_expiration ? 1 : 0);

            /**
                $zone->fast_regen_hp        = array_get($row, 'fast_regen_hp');
                $zone->fast_regen_mana      = array_get($row, 'fast_regen_mana');
                $zone->fast_regen_endurance = array_get($row, 'fast_regen_endurance');
                $zone->npc_max_aggro_dist   = array_get($row, 'npc_agro_max_dist');
             */

            $zone->save();

            $count++;
        }

        $this->setCreatedCount("zone", $count);

        return $this;
    }

    /**
     * @param string $zone_short_name
     * @return int|null
     */
    public function getZoneIdByShortName(string $zone_short_name): ?int
    {
        return Zone::where('short_name', $zone_short_name)->first()->zoneidnumber;
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
     * @return ZoneDataDumpImportService
     */
    public function setFile(string $file): ZoneDataDumpImportService
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
     * @return ZoneDataDumpImportService
     */
    public function setZoneShortName(string $zone_short_name): ZoneDataDumpImportService
    {
        $this->zone_short_name = $zone_short_name;

        return $this;
    }

    /**
     * @param int $zone_instance_version
     * @return ZoneDataDumpImportService
     */
    public function setZoneInstanceVersion(int $zone_instance_version): ZoneDataDumpImportService
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

    /**
     * @param string $type
     * @param int    $count
     * @return ZoneDataDumpImportService
     */
    private function setCreatedCount(string $type, int $count): ZoneDataDumpImportService
    {
        $this->created_count[$type] = $count;

        return $this;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getCreatedCount(string $type): array
    {
        return array_get($this->created_count, $type, []);
    }

    /**
     * @return array
     */
    public function getCreated(): array
    {
        return $this->created_count;
    }
}
