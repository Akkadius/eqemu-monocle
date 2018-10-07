<?php

namespace App\Console\Commands;

use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
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
    public function handle()
    {
        /**
         * Get args
         */
        $zone_short_name       = $this->argument('zone_short_name');
        $zone_instance_version = $this->argument('zone_instance_version');

        /**
         * Get spawn2 entries
         */
        $spawn2_entries = Spawn2::where(
            [
                'zone'    => $zone_short_name,
                'version' => $zone_instance_version
            ]
        )->get();

        $spawn_group_ids = [];
        foreach ($spawn2_entries as $spawn2_entry) {
            $spawn_group_ids[] = $spawn2_entry->spawngroupID;

            $spawn2_entry->delete();
        }

        $this->info("Deleted 'spawn2' (" . count($spawn2_entries) . ")...");

        /**
         * Get spawn group entries
         */
        $spawn_group_entries = SpawnGroup::whereIn('id', $spawn_group_ids)->get();
        foreach ($spawn_group_entries as $spawn_group_entry) {
            $spawn_group_entry->delete();
        }

        $this->info("Deleted 'spawngroup' (" . count($spawn_group_entries) . ")...");

        /**
         * Get spawnentry
         */
        $spawn_entries = SpawnEntry::whereIn('spawngroupID', $spawn_group_ids)->get();
        $npc_type_ids = [];
        foreach ($spawn_entries as $spawn_entry) {
            $npc_type_ids[] = $spawn_entry->npcID;

            $spawn_entry->delete();
        }

        $this->info("Deleted 'spawnentry' (" . count($spawn_entries) . ")...");

        /**
         * Delete NPC's
         */
        NpcTypes::whereIn('id', $npc_type_ids)->delete();

        $this->info("Deleted 'npc_types' (" . count($npc_type_ids) . ")...");
    }
}
