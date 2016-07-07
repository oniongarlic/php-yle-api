#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

$r=file_get_contents('../data/playlist-test.html');

$p=new PYle\YleScrobblerParser();

$p->parseHTML($r);

?>
