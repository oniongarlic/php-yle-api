#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

// Start date is 2013-06-07
// Base url is https://svenska.yle.fi/spellista/yle-x3m/
// Source is https://svenska.yle.fi/spellista/yle-x3m/2013-06-07

// Save artists
function save_artists($artists)
{
$fp = fopen('artists.csv', 'w');
fputcsv($fp, array('a-hash','artist','related-all','r1','r2','r3','r4','r5'));
foreach ($artists as $id => $a) {
 $t=array_merge(array($id), $a);
 if (count($t)>7) echo $a[0]." AR>7! \n";
 fputcsv($fp, $t);
}
fclose($fp);
}

function save_songs($songs)
{
$fp = fopen('songs.csv', 'w');
fputcsv($fp, array('s-hash','artist','song','count'));
foreach ($songs as $id=>$s) {
 fputcsv($fp, array($id, $s['artist'], $s['song'], $s['count']));
}
fclose($fp);
}

// Save progams that played
function save_programs($programs)
{
$fp = fopen('programs.csv', 'w');
fputcsv($fp, array('program'));
foreach ($programs as $a) {
 fputcsv($fp, array($a));
}
fclose($fp);
}

// Save playlists
function save_playlists($playlists)
{
$fp = fopen('playlist.csv', 'w');
fputcsv($fp, array('date','sa-hash','artist','song','program','channel','time','duration'));
foreach ($playlists as $date=>$songs) {
 foreach ($songs as $song) {
  $tmp=array($date.' '.$song['time']);
  $tmp+=$song;
  fputcsv($fp, $tmp);
 }
}
fclose($fp);
}

///////////////////////////////////////////////////////////////////////////////

$from=new DateTime('2013-06-07');
$to=new DateTime();
//$to=new DateTime('2013-06-18');

$base='https://svenska.yle.fi/spellista/yle-x3m/';
$cachedir='./cache/';

$artists=array();
$programs=array();
$playlists=array();
$songs=array();

printf("Downloading from %s until %s\n", $from->format('Y-m-d'), $to->format('Y-m-d'));

$p=new PYle\YleScrobblerParser();
while ($from <= $to) {
 $ymd=$from->format('Y-m-d');

 echo "$ymd";

 $url=sprintf('%s%s', $base, $ymd);

 $cf=$cachedir.$ymd;
 $cached=false;

 if (!file_exists($cf)) {
  echo "-d";
  $data=file_get_contents($url);
 } else {
  echo "-c";
  $cached=true;
  $data=file_get_contents($cf);
 }

 if (!$p->parseHTML($data)) {
  echo "?";
 } else if (!$cached && ($from<$to)) {
  // Cache the downloaded data only if parsing was ok
  echo "!";
  file_put_contents($cf, $data);
 }

 echo "\n";

 $s=$p->getPlaylist();
 $playlists[$ymd]=$s;

 $from->add(new DateInterval('P1D'));
}

$artists=$p->getArtists();
$programs=$p->getPrograms();

sort($programs);

save_playlists($playlists);
save_artists($artists);
save_songs($p->getSongs());
save_programs($programs);

?>
