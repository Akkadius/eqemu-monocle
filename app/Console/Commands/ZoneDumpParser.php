<?php

namespace App\Console\Commands;

use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
use App\Services\DataDumpReaderService;
use Illuminate\Console\Command;

/**
 * Class ZoneDumpParser
 * @package App\Console\Commands
 */
class ZoneDumpParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zonedump:parse';

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
     */
    public function handle(DataDumpReaderService $data_dump_reader_service)
    {
        // $npc = NpcTypes::find(55094);
//
        // dd($npc);

        /**
         * Setup reader service
         */
        $data_dump_reader_service
            ->setFile('Thundercrest_NPC_2018-10-06-16-17-12.csv')
            ->initReader()
            ->parse();

        $interested_fields = [
            "body_type",
            "get_class", // class
            "heading",
            "height", // size
            "lastname",
            "m_actor_client_class",
            "m_actor_client_details",
            "m_actor_client_eye_color1",
            "m_actor_client_eye_color2",
            "m_actor_client_face_style",
            "m_actor_client_facial_hair",
            "m_actor_client_facial_hair_color",
            "m_actor_client_gender",
            "m_actor_client_hair_color",
            "m_actor_client_hair_style",
            "m_actor_client_head_type",
            "m_actor_client_heritage",
            "m_actor_client_material",
            "m_actor_client_race", // race
            "m_actor_client_tattoo",
            "m_actor_client_texture_type", // texture
            "m_actor_client_variation",
            "max_speak_distance",
            "name",
            "see_invis0",
            "x",
            "y",
            "z"
            // "avatar_height", // Which height do we use
            // "melee_radius",
            // "walk_speed",
            // Findable?
            // Trackable?
        ];

        foreach ($data_dump_reader_service->getCsvData() as $row) {

            /**
             * Create NPC
             */
            $npc            = new NpcTypes;
            $npc->name      = array_get($row, 'name');
            $npc->level     = array_get($row, 'level');
            $npc->lastname  = array_get($row, 'lastname');
            $npc->size      = array_get($row, 'height');
            $npc->bodytype  = array_get($row, 'body_type');
            $npc->class     = array_get($row, 'get_class');
            $npc->race      = array_get($row, 'm_actor_client_race');
            $npc->texture   = array_get($row, 'm_actor_client_texture_type');
            $npc->gender    = array_get($row, 'm_actor_client_gender');

            $see_invis      = array_get($row, 'see_invis0');
            $npc->see_invis = ($see_invis ? 1 : 0);
            $npc->save();

            /**
             * Create Spawn Group
             */
            $spawn_group       = new SpawnGroup;
            $spawn_group->name = "test_group_" . md5(rand());
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
            $spawn2->zone         = 'thundercrest';
            $spawn2->x            = array_get($row, 'x');
            $spawn2->y            = array_get($row, 'y');
            $spawn2->z            = array_get($row, 'z');
            $spawn2->heading      = array_get($row, 'heading');
            $spawn2->save();

            dump($npc->id);
        }
    }


}
