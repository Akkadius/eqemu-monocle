<?php
/**
 * Created by PhpStorm.
 * User: cmiles
 * Date: 10/7/18
 * Time: 2:51 AM
 */

namespace App\Services;


use App\Models\Door;
use App\Models\GroundSpawn;
use App\Models\NpcTypes;
use App\Models\Spawn2;
use App\Models\SpawnEntry;
use App\Models\SpawnGroup;
use App\Models\Zone;
use App\Models\ZonePoint;

class ZoneDataDeleteService
{

    /**
     * @var string
     */
    protected $zone_short_name;

    /**
     * @var int
     */
    protected $zone_instance_version;

    /**
     * @var
     */
    protected $process_messages;

    /**
     * Delete everything
     *
     * @throws \Exception
     */
    public function deleteAll()
    {
        $this
            ->deleteNpcData()
            ->deleteDoorData()
            ->deleteZonePointData();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function deleteNpcData()
    {
        /**
         * Get spawn2 entries
         */
        $spawn2_entries = Spawn2::where(
            [
                'zone'    => $this->getZoneShortName(),
                'version' => $this->getZoneInstanceVersion()
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
        
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function deleteDoorData()
    {
        $deleted_count = Door::where(
            [
                'zone'    => $this->getZoneShortName(),
                'version' => $this->getZoneInstanceVersion()
            ]
        )->delete();

        $this->info("Deleted 'doors' (" . $deleted_count . ")...");

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function deleteZonePointData()
    {
        $deleted_count = ZonePoint::where(
            [
                'zone'    => $this->getZoneShortName(),
                'version' => $this->getZoneInstanceVersion()
            ]
        )->delete();

        $this->info("Deleted 'zone_points' (" . $deleted_count . ")...");

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function deleteGroundSpawnData()
    {
        $deleted_count = GroundSpawn::where(
            [
                'zoneid'    => $this->getZoneIdByShortName($this->getZoneShortName()),
                'version' => $this->getZoneInstanceVersion()
            ]
        )->delete();

        $this->info("Deleted 'ground_spawns' (" . $deleted_count . ")...");

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
     * @param string $zone_short_name
     * @return ZoneDataDeleteService
     */
    public function setZoneShortName(string $zone_short_name): ZoneDataDeleteService
    {
        $this->zone_short_name = $zone_short_name;

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
     * @param int $zone_instance_version
     * @return ZoneDataDeleteService
     */
    public function setZoneInstanceVersion(int $zone_instance_version): ZoneDataDeleteService
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
     * @param string $string
     */
    private function info(string $string)
    {
        $this->process_messages = $this->process_messages . $string . "\n";
    }

    /**
     * @return mixed
     */
    public function getProcessMessages()
    {
        return $this->process_messages;
    }
}