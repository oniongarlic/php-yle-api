#!/usr/bin/php -q
<?php
require_once('lib/YleApi.php');

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die('Configuration file config.ini is missing');

$api=$config['Generic'];

$pid='1-2897178';
$mid='6-78660c2225684dad9b5cba6f82e0ded7';

$c=new YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
$c->set_debug(true);

// XXX user specific so
$url='dlwHSpwYjjmjyLnBBNb//bZ3tvjXHEcB82/xKuiXPDYxhq0aKqwRmgdmdQXluzcbymnINyC4BopjrHSrt7r7GROgcIsZ+7ERsEMOG0MOWqGKgtbTIej3EdzCvNkWbEJ9tREpszaZ6Cf/l1hBoL0ie6KgeE0FtWNIqB1SRl8PyA/aZwLPYaSTVzFS0IFVI5wHpH2edTmyxZbSsfcvjNc0p2BqyouN9QkqJ1HEG+YslfzdzjTTWkdGEVJkntqRThV/SuffM9qEwV77p4uPNiYaspAZRy9Jb9cG3Fc3dUEuLUyXiE/HxuWWZIxyOGZmrl88xbeeY+qfBLLqRDePWJz4j05MSk/cNB9Nq4myOuM0q7VuJliB8youqpond0sfxpvapinV+l8ii6G4MmqIOaB1Vw==';

echo $c->media_url_decrypt($url);

?>
