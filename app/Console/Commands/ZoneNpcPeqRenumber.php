<?php

namespace App\Console\Commands;

use App\Models\Zone;
use DB;
use Exception;
use Illuminate\Console\Command;

class ZoneNpcPeqRenumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zonetools:npc-type-renumber
        {zone_short_name}
        {zone_instance_version}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handles renumbering NPC data that does not fall within PEQ convention';

    /**
     * @var int
     */
    protected $zone_id;

    /**
     * @var int
     */
    protected $npc_types_range_min;

    /**
     * @var int
     */
    protected $npc_types_range_max;

    /**
     * @var string
     */
    protected $zone_short_name;

    /**
     * @var int
     */
    protected $zone_version;

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
     * @throws Exception
     */
    public function handle()
    {
        /**
         * Vars
         */
        $this->zone_short_name     = $this->argument('zone_short_name');
        $this->zone_version        = $this->argument('zone_instance_version');
        $this->zone_id             = Zone::getZoneIdByShortName($this->zone_short_name);
        $this->npc_types_range_min = ($this->zone_id * 1000);
        $this->npc_types_range_max = (($this->zone_id + 1) * 1000);

        $this->info(
            sprintf("Zone: '%s' Version: %s NPCID Min/Max (%s/%s)",
                $this->zone_short_name,
                $this->zone_version,
                $this->npc_types_range_min,
                $this->npc_types_range_max
            )
        );

        /**
         * Fetch npc types that are within a zone, but outside PEQ ID convention
         */
        $npcs = DB::table('npc_types')
            ->select('npc_types.*')
            ->join('spawnentry', 'npc_types.id', '=', 'spawnentry.npcID')
            ->join('spawn2', 'spawnentry.spawngroupID', '=', 'spawn2.spawngroupID')
            ->whereRaw('(npc_types.id < ' . $this->npc_types_range_min . ' OR npc_types.id > ' . $this->npc_types_range_max . ')')
            ->where('spawn2.zone', $this->zone_short_name)
            ->where('spawn2.version', $this->zone_version)
            ->distinct()
            ->get();

        foreach ($npcs as $npc) {
            $next_npc_id = $this->getNextNpcId();

            /**
             * Relink npc_types
             */
            DB::table('npc_types')
                ->where('id', $npc->id)
                ->update(['id' => $next_npc_id]);

            /**
             * Relink spawn entries
             */
            DB::table('spawnentry')
                ->where('npcID', $npc->id)
                ->update(['npcID' => $next_npc_id]);

            $this->info("Relinking NPC ({$npc->name}) from ID {$npc->id} to {$next_npc_id}");
        }
    }

    /**
     * @return int|null
     * @throws Exception
     */
    private function getNextNpcId(): ?int
    {
        /**
         * Fetch highest from range in DB
         */
        $next_id_to_use = DB::table('npc_types')
            ->selectRaw('id + 1 as next_id')
            ->where(
                [
                    ['id', '>', $this->npc_types_range_min],
                    ['id', '<', $this->npc_types_range_max]
                ]
            )
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first()
            ->next_id;

        if ($next_id_to_use > $this->npc_types_range_max) {
            throw new Exception("Cannot use ID of $next_id_to_use since our current max is $npc_types_range_max");
        }

        return $next_id_to_use;
    }
}
