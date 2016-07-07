#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

// Start date is 2013-06-07
// Base url is https://svenska.yle.fi/spellista/yle-x3m/
// Source is https://svenska.yle.fi/spellista/yle-x3m/2013-06-07

$from=new DateTime('2013-06-07');
$to=new DateTime('2013-06-10');

$base='https://svenska.yle.fi/spellista/yle-x3m/';
$cachedir='./cache/';

$artists=array();
$programs=array();
$playlists=array();

while ($from <= $to) {
 $p=new PYle\YleScrobblerParser();

 $ymd=$from->format('Y-m-d');

 echo "$ymd\n";

 $url=sprintf('%s%s', $base, $ymd);

 $cf=$cachedir.$ymd;

 if (!file_exists($cf)) {
  $data=file_get_contents($url);
  file_put_contents($cf, $data);
  sleep(1);
 } else {
  $data=file_get_contents($cf);
 }

 $p->parseHTML($data);

 $s=$p->getPlaylist();
 $playlists[$ymd]=$s;

 $artists+=array_unique($p->getArtists());
 $programs+=array_unique($p->getPrograms());

 $from->add(new DateInterval('P1D'));
}

$fp = fopen('playlist.csv', 'w');
foreach ($playlists as $date=>$songs) {
 foreach ($songs as $song) {
  $tmp=array($date);
  $tmp+=$song;
  print_r($tmp);
  fputcsv($fp, $tmp);
 }
}
fclose($fp);

$fp = fopen('artists.csv', 'w');
foreach ($artists as $c=>$a) {
 fputcsv($fp, array($a, $c));
}
fclose($fp);

//print_r($artists);
//print_r($playlists);

?>
