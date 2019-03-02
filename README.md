The purpose of this project is to handle database imports from CSV dumps namely from project https://github.com/maudigan/MQ2TakeADump

# Setup

In monocle folder

`cp .env.example .env`

Change your DB variables to point to the appropriate database of your choosing

## Laradock Init (Docker)

For this project its easy to just pull in laradock because its easy to grab and can get us setup pretty quickly

```
git clone https://github.com/Laradock/laradock.git
cd laradock
cp env-example .env
docker-compose up -d mariadb workspace
```

## Workspace Bash

From laradock folder

```
docker-compose exec workspace bash
```
Once you are in the bash, you will be volume mounted to your code directory, from here issue the following commands

```
composer global require hirak/prestissimo
composer install -o
```

From there our CLI environment should be ready to go

## Seeding a PEQ Database for Testing (From workspace container)

If you would like to seed some data for testing, the follow works fine

```
apt-get update && apt-get install -y wget unzip mysql-client

wget http://edit.peqtgc.com/weekly/peq_beta.zip -O /tmp/peq_beta.zip
cd /tmp/
unzip -o peq_beta.zip

mysql -h mariadb -uroot -proot -e "drop database peq; CREATE DATABASE peq"
mysql -h mariadb -uroot -proot peq < peqbeta.sql
mysql -h mariadb -uroot -proot peq < player_tables.sql
```

## Monocle

Current tools as of this writing

```
php artisan | grep zonetools
 zonetools
  zonetools:dump-import        Parses CSV zone dumps
  zonetools:npc-type-renumber  Handles renumbering NPC data that that are outside PEQ ID convention
  zonetools:zone-delete        Deletes entity data in a zone
```

### CSV Dump Storage Location

When you have dumps to import, place them in the following directory (from root)

```
storage/app/
```

It should look like the following

```
ls -l
total 1352
-rw-r--r--@ 1 cmiles  staff   27747 Jan  2 18:16 Thundercrest_Door_2018-10-15-12-17-25.csv
-rw-r--r--@ 1 cmiles  staff     278 Jan  2 18:16 Thundercrest_GroundItem_2018-10-15-12-17-25.csv
-rw-r--r--@ 1 cmiles  staff  639173 Jan  2 18:16 Thundercrest_NPC_2018-10-15-12-17-25.csv
-rw-r--r--@ 1 cmiles  staff     278 Jan  2 18:16 Thundercrest_Objects_2018-10-15-12-17-25.csv
-rw-r--r--@ 1 cmiles  staff     209 Jan  2 18:16 Thundercrest_ZonePoint_2018-10-15-12-17-25.csv
-rw-r--r--@ 1 cmiles  staff    4987 Jan  2 18:16 Thundercrest_Zone_2018-10-15-12-17-25.csv
drwxr-xr-x  3 cmiles  staff      96 Jan  2 18:19 public
```
# Importing a Zone

```php
    protected $signature = 'zonetools:dump-import
        {zone_short_name}
        {zone_instance_version}
        {dump_type : npc|door|object|groundspawn|zonepoint|zone|all}
        {--s|skip-confirmation : Skips file prompt confirmation}
    ';
```
### Importing just NPCs

```
php artisan zonetools:dump-import thundercrest 0 npc
```

### Importing all 

```
php artisan zonetools:dump-import thundercrest 0 all -s
```

# Deleting a Zone

```php
    protected $signature = 'zonetools:zone-delete 
        {zone_short_name} 
        {zone_instance_version}
        {delete_type : npc|door|object|groundspawn|zonepoint|zone|all}
        ';
```


```
php artisan zonetools:zone-delete thundercrest 0 all
```

Example of just deleting NPCs

```
php artisan zonetools:zone-delete poknowledge 0 npc
Deleted 'spawn2' (438)...
Deleted 'spawngroup' (438)...
Deleted 'spawnentry' (438)...
Deleted 'npc_types' (438)...
```
