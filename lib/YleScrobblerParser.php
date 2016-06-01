<?php

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

function __construct()
{
$this->data=array();
$this->doc = new DOMDocument();
}

public function parseHTML($html)
{
$this->data=array();
$r=@$this->doc->loadHTML($html);
$x = new DOMXpath($this->doc);
$it=htmlspecialchars('http://schema.org/MusicRecording');
$q="//li[@itemtype='".$it."']";
$items=$x->query($q);
if (is_null($items))
	return false;

$qa=".//*[@itemprop='byArtist']/*[@itemprop='name']";
$qs=".//*[@itemprop='name']";

foreach ($items as $item) {
	$s=array();
	$ix=new DOMXpath($this->doc);

	$item1=$ix->query($qa, $item);
	$item2=$ix->query($qs, $item);

	$artist=$item1->item(0)->nodeValue;
	$song=$item2->item(0)->nodeValue;

	$s=array('artist'=>$artist, 'song'=>$song);

	print_r($s);

	$this->data[]=$s;
}

return $res ? true : false;
}

public function getPlaylist()
{
return $this->data;
}

}

?>
