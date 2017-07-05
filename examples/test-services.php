#!/usr/bin/php -q
<?php
require_once('../lib/YleApi.php');

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die("Configuration file config.ini is missing\n");

if ($argc!==2)
	$t=false;
else
	$t=$argv[1];

$api=$config['Generic'];

$c=new PYle\YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
$c->set_debug(true);

$a=$c->programs_services($t);
file_put_contents('services.json', json_encode($a, JSON_PRETTY_PRINT));
print_r($a);
?>
