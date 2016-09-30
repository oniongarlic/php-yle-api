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

public function toJSON()
{
$tmp=array(
	'playlist'=>$this->data,
	'programs'=>$this->programs,
	'artists'=>$this->artists,
	'songs'=>$this->songs
);

return json_encode($tmp);
}

public function fromJSON($data)
{
$tmp=json_decode($data);
if (!$tmp)
	return false;

$this->playlist=$tmp['playlist'];
$this->songs=$tmp['songs'];
$this->artists=$tmp['artists'];
$this->programs=$tmp['programs'];

return true;
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

private function addSong($song, $artist, $ahash, $shash)
{
if (array_key_exists($shash, $this->songs)) {
	$this->songs[$shash]['count']++;
	return;
}
$this->songs[$shash]=array('count'=>1, 'song'=>$song, 'artist'=>$artist, 'ahash'=>$ahash);
}

public function parseHTML($html)
{
$r=@$this->doc->loadHTML($html);
$this->data=array();
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
$qc=".//span[@class='channelname']";

foreach ($items as $item) {
	$s=array();
	$ix=new \DOMXpath($this->doc);

	$item1=$ix->query($qa, $item);
	$item2=$ix->query($qs, $item);
	$item3=$ix->query($qd, $item);
	$item4=$ix->query($qt, $item);
	$item5=$ix->query($qp, $item);
	$item6=$ix->query($qc, $item);

	$artist=$item1->item(0)->nodeValue;
	$song=$item2->item(0)->nodeValue;
	$duration=$item3->item(0)->nodeValue;
	$time=$item4->item(0)->nodeValue;
	$program=$item5->item(0)->nodeValue;
	$channel=$item6->item(0)->nodeValue;

	$sahash=hash_hmac('sha256', $song, $artist, false);
	$ahash=hash('sha256', $artist, false);

	$s=array('hash'=>$sahash,
		'artist'=>$artist,
		'ahash'=>$ahash,
		'song'=>$song,
		'program'=>$program,
		'channel'=>$channel,
		'time'=>$time,
		'duration'=>$duration);

	$this->data[]=$s;

	if (strstr($artist, ' & ')) {
		$ra=explode(' & ', $artist);
		$this->artists[$ahash]=array_merge(array($artist, implode(';',$ra)), $ra);
	} else {
		$this->artists[$ahash]=array($artist, '');
	}

	$this->programs[]=$program;
	$this->addSong($song, $artist, $ahash, $sahash);
	$ix=null;
}
// XXX: no..nonono!!
$this->programs=array_unique($this->programs);

return count($this->data)>0 ? true : false;
}

}

?>
