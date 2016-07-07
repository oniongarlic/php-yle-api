#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

$r=file_get_contents('../data/playlist-test.html');

$p=new PYle\YleScrobblerParser();

$p->parseHTML($r);

$s=$p->getPlaylist();
$a=array_unique($p->getArtists());
$pr=array_unique($p->getPrograms());

print_r($s);
print_r($a);
print_r($pr);

?>
