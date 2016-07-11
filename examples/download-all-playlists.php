#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

// Start date is 2013-06-07
// Base url is https://svenska.yle.fi/spellista/yle-x3m/
// Source is https://svenska.yle.fi/spellista/yle-x3m/2013-06-07

$from=new DateTime('2013-06-07');
$to=new DateTime();

$base='https://svenska.yle.fi/spellista/yle-x3m/';
$cachedir='./cache/';

$artists=array();
$programs=array();
$playlists=array();

printf("Downloading from %s until %s\n", $from->format('Y-m-d'), $to->format('Y-m-d'));

while ($from <= $to) {
 $p=new PYle\YleScrobblerParser();

 $ymd=$from->format('Y-m-d');

 echo "$ymd";

 $url=sprintf('%s%s', $base, $ymd);

 $cf=$cachedir.$ymd;

 if (!file_exists($cf)) {
  echo "-c\d";
  $data=file_get_contents($url);
  file_put_contents($cf, $data);
 } else {
  echo "-c\n";
  $data=file_get_contents($cf);
 }

 $p->parseHTML($data);

 $s=$p->getPlaylist();
 $playlists[$ymd]=$s;

 $artists=array_merge($artists, array_unique($p->getArtists()));
 $programs=array_merge($programs, array_unique($p->getPrograms()));

 $from->add(new DateInterval('P1D'));
}

sort($artists);
sort($programs);

$artists=array_unique($artists);
$programs=array_unique($programs);

// Save playlists
$fp = fopen('playlist.csv', 'w');
foreach ($playlists as $date=>$songs) {
 foreach ($songs as $song) {
  $tmp=array($date);
  $tmp+=$song;
  fputcsv($fp, $tmp);
 }
}
fclose($fp);

// Save artists
$fp = fopen('artists.csv', 'w');
foreach ($artists as $a) {
 fputcsv($fp, array($a));
}
fclose($fp);

// Save progams that played
$fp = fopen('programs.csv', 'w');
foreach ($programs as $a) {
 fputcsv($fp, array($a));
}
fclose($fp);

?>
