#!/usr/bin/php -q
<?php
require_once('lib/YleApi.php');

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die('Configuration file config.ini is missing');

$api=$config['Generic'];

$pid='1-2897178';

$c=new YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
$c->set_debug(true);

$a=$c->programs_item($pid);

$mid=$c->media_find_ondemand_publication_media_id($a);

if ($mid===false)
	die('Failed to find ondemand media!');

$a=$c->media_playouts($pid, $mid);
print_r($a);

$eurl=$a->data[0]->url;

$url=$c->media_url_decrypt($eurl);

printf("Media URL is:\n\n%s\n\n", $url);

system("mplayer \"$url\"");
?>
