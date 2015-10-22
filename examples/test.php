#!/usr/bin/php -q
<?php
require_once('../lib/YleApi.php');

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die('Configuration file config.ini is missing');

$api=$config['Generic'];

$c=new YleApiClient($api['app_id'], $api['app_key'], '');
$c->set_debug(true);

$a=$c->programs_services();
file_put_contents('services.txt', json_encode($a, JSON_PRETTY_PRINT));

$a=$c->programs_items();
file_put_contents('items.txt', json_encode($a, JSON_PRETTY_PRINT));

$a=$c->programs_service('yle-x3m');
file_put_contents('x3m.txt', json_encode($a, JSON_PRETTY_PRINT));

$a=$c->programs_nowplaying('yle-x3m');
file_put_contents('nowplaying-x3m.txt', json_encode($a, JSON_PRETTY_PRINT));


?>
