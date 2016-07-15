<?php
namespace PYle;

/**
 * Class that parses a YLE playlist HTML page content for 
 * playlist information.
 *
 * YLE playlists uses http://schema.org/MusicRecording markup for tracks in their HTML
 * For example
 * http://svenska.yle.fi/spellista/yle-x3m/2013-06-07
 *
 */

class YleScrobblerParser
{
private $data;
private $doc;
private $programs;
private $artists;
private $songs;

function __construct()
{
$this->data=array();
$this->programs=array();
$this->artists=array();
$this->songs=array();
$this->doc = new \DOMDocument();
}

public function getPlaylist()
{
return $this->data;
}

public function getPrograms()
{
return $this->programs;
}

public function getArtists()
{
return $this->artists;
}

public function getSongs()
{
return $this->songs;
}

private function addSong($song, $artist, $hash)
{
if (array_key_exists($hash, $this->songs)) {
	$this->songs[$hash]['count']++;
	return;
}
$this->songs[$hash]=array('count'=>1, 'song'=>$song, 'artist'=>$artist);
}

public function parseHTML($html)
{
$this->data=array();
$r=@$this->doc->loadHTML($html);
$x = new \DOMXpath($this->doc);
$it=htmlspecialchars('http://schema.org/MusicRecording');
$q="//li[@itemtype='".$it."']";
$items=$x->query($q);
if (is_null($items))
	return false;

$qa=".//*[@itemprop='byArtist']/span[@itemprop='name']";
$qs=".//span[@itemprop='name' and @class='song']";
$qd=".//*[@itemprop='duration']/@content";
$qt=".//span[@class='time']";
$qp=".//span[@class='program']";

foreach ($items as $item) {
	$s=array();
	$ix=new \DOMXpath($this->doc);

	$item1=$ix->query($qa, $item);
	$item2=$ix->query($qs, $item);
	$item3=$ix->query($qd, $item);
	$item4=$ix->query($qt, $item);
	$item5=$ix->query($qp, $item);

	$artist=$item1->item(0)->nodeValue;
	$song=$item2->item(0)->nodeValue;
	$duration=$item3->item(0)->nodeValue;
	$time=$item4->item(0)->nodeValue;
	$program=$item5->item(0)->nodeValue;

	$sahash=hash_hmac('sha256', $song, $artist, false);

	$s=array('hash'=>$sahash,
		'artist'=>$artist,
		'song'=>$song,
		'program'=>$program,
		'time'=>$time,
		'duration'=>$duration);

	$this->data[]=$s;
	$this->artists[]=$artist;
	$this->programs[]=$program;
	$this->addSong($song, $artist, $hash);
}

// XXX: no..nonono!!
$this->artists=array_unique($this->artists);
$this->programs=array_unique($this->programs);

return count($this->data)>0 ? true : false;
}

}

?>
