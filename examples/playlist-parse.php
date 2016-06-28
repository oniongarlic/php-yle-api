#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

$r=file_get_contents('playlist-test.html');

$p=new PYle\YleScrobblerParser();

$p->parseHTML($r);

?>
