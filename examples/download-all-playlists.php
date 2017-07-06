#!/usr/bin/php -q
<?php
require_once('../lib/YleScrobblerParser.php');

// Start date is 2013-06-07
// Base url is https://svenska.yle.fi/spellista/yle-x3m/
// Source is https://svenska.yle.fi/spellista/yle-x3m/2013-06-07

class PlaylistCSVSaver
{
private $cid;

function __construct($cid)
{
$this->cid=$cid;
}

// Save unique artists
function save_artists(array $artists)
{
$fp = fopen($this->cid.'-artists.csv', 'w');
fputcsv($fp, array('a-hash','artist','related-all','r1','r2','r3','r4','r5'));
foreach ($artists as $id => $a) {
 $t=array_merge(array($id), $a);
 if (count($t)>7) echo $a[0]." AR>7! \n";
 fputcsv($fp, $t);
}
fclose($fp);
}

// Save unique songs, with artist details
function save_songs(array $songs)
{

$fp = fopen($this->cid.'-songs.csv', 'w');
fputcsv($fp, array('sa-hash','a-hash','artist','song','count'));
foreach ($songs as $id=>$s) {
 fputcsv($fp, array($id, $s['ahash'], $s['artist'], $s['song'], $s['count']));
}
fclose($fp);
}

// Save unique progams that played
function save_programs(array $programs)
{
$fp = fopen($this->cid.'-programs.csv', 'w');
fputcsv($fp, array('program'));
foreach ($programs as $a) {
 fputcsv($fp, array($a));
}
fclose($fp);
}

// Save playlists
function save_playlists(array $playlists)
{
$fp = fopen($this->cid.'-playlist.csv', 'w');
fputcsv($fp, array('date','sa-hash','artist','a-hash','song','program','channel','time','duration'));
foreach ($playlists as $date=>$songs) {
 foreach ($songs as $song) {
  $tmp=array($date.' '.$song['time']);
  $tmp+=$song;
  fputcsv($fp, $tmp);
 }
}
fclose($fp);
}

} // class

function parse_playlist($base, $cache, &$p, DateTime &$from, DateTime &$to)
{
$ymd=$from->format('Y-m-d');

echo "$ymd";

$url=sprintf('%s%s', $base, $ymd);

$cf=$cache.$ymd;
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
$data=null;
echo "\n";
}

///////////////////////////////////////////////////////////////////////////////

function downloadPlaylistForChannel($cid, DateTime $from)
{
$to=new DateTime();
//$to=new DateTime('2013-06-18');

$base='https://svenska.yle.fi/spellista/'.$cid.'/';
$cachedir='./cache/'.$cid.'/';

$artists=array();
$programs=array();
$playlists=array();
$songs=array();

printf("Downloading data for %s from %s until %s\n", $cid, $from->format('Y-m-d'), $to->format('Y-m-d'));

$p=new PYle\YleScrobblerParser();

while ($from <= $to) {
 $ymd=$from->format('Y-m-d');
 parse_playlist($base, $cachedir, $p, $from, $to);
 $s=$p->getPlaylist();
 $playlists[$ymd]=$s;
 $from->add(new DateInterval('P1D'));
 gc_collect_cycles();
}

$programs=$p->getPrograms();

sort($programs);
$s=new PlaylistCSVSaver($cid);

$s->save_playlists($playlists);
$s->save_artists($p->getArtists());
$s->save_songs($p->getSongs());
$s->save_programs($programs);
}

gc_enable();

$from=new DateTime('2013-06-07');
downloadPlaylistForChannel('yle-x3m', $from);

$from=new DateTime('2013-06-09');
downloadPlaylistForChannel('yle-vega', $from);
?>
