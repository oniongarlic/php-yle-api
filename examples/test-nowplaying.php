#!/usr/bin/php -q
<?php
require_once('../lib/YleApi.php');

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die("Configuration file config.ini is missing\n");

if ($argc!==2)
	die("Channel ID required!\n");

$pid=$argv[1];
$api=$config['Generic'];

$c=new YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
$c->set_debug(true);

$a=$c->programs_nowplaying($pid);
print_r($a);
?>
