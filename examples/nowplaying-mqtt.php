#!/usr/bin/php -q
<?php
require_once('../lib/YleApi.php');

class YleNowplayingUpdater
{
private $mqtt;
private $c;
private $pid;
private $sleepTime=4;
private $queue;

function __construct($pid, $config)
{
$this->queue=array();
$api=$config['Generic'];
$mqtt=$config['MQTT'];

// XXX use ini for mqtt server
$this->pid=$pid;

$this->mqtt=new Mosquitto\Client('talorg-yle-nowplaying', true);
$this->mqtt->setWill('radio/nowplaying/active', 0, 1, true);
$this->mqtt->connect($mqtt['host'], 1883);
$this->mqtt->publish('radio/nowplaying/active', 1);

$this->c=new YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
}

private function getChannelTopic($broadcaster, $id, $offset, $item)
{
// radio/yle/x3m/0/item
return sprintf('radio/%s/%s/%d/%s', $broadcaster, $id, $offset, $item);
}

private function publishSong($delta, array $data)
{
$tmp=array(
	'id'=>$data['id'],
	's'=>$data['start']->format('Y-m-d H:i:s'),
	'e'=>$data['end']->format('Y-m-d H:i:s'),
	't'=>$data['title'],
	'p'=>$data['performer']
	);
echo "DELTA: $delta\n"; print_r($tmp);
$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'json'), json_encode($tmp), 1, true);
$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'title'), $data['title'], 1, true);
$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'performer'), $data['performer'], 1, true);
}

private function publishProgram(array $data)
{
$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, 0, 'program'), $data['program']->sv, 1, true);
}

private function refreshNowplaying()
{
$a=$this->c->programs_nowplaying($this->pid);
return YleNowplaying::create($a);
}

private function updateNext($i)
{
$tmp=$this->np->get($i);

if ($tmp===false)
	return false;
if ($this->queue[$i]['id']==$tmp['id'])
	return true;

echo "N";
$this->queue[$i]=$tmp;
$this->publishSong($i, $this->queue[$i]);
// $this->publishProgram($this->queue[$i]);

return true;
}

public function run()
{
$this->np=$this->refreshNowplaying();
$cnt=0;
$nf=false;

// Set defaults
$this->queue[-2]=$this->np->get(-2);
$this->queue[-1]=$this->np->get(-1);
$this->queue[0]=$this->np->get(0);
$this->queue[1]=$this->np->get(1);
$this->queue[2]=$this->np->get(2);
$pcur=false;

$this->publishProgram($this->queue[0]);

foreach ($this->queue as $delta=>$song) {
	if ($song!==false)
		$this->publishSong($delta, $song);
	// XXX we should probably clear previous ones...
}

while (true) {
	echo ".";
	$this->mqtt->loop(4000);

	$cnt++;

	$pcur=$this->queue[0];
	$this->queue[0]=$this->np->getCurrent();

	/*
	We can be in various states, current song or in between
	1. A current song is playing now (we are inside start <-> end
	2. We are outside a current song
	3. We have exhaused the list and need to refresh

	In case we are outside a current song, but the list is still valid then we use delta 1 to set the next song
	*/
	if ($this->queue[0]===false && $this->np->isValid()) {
		$this->updateNext(1);
		$this->updateNext(2);
	} else if ($this->queue[0]!==false && $this->queue[0]['id']!=$pcur['id']) {
		echo "C";
		$this->queue[-1]=$pcur;
		$this->publishSong(0, $this->queue[0]);
		$this->publishSong(-1, $this->queue[-1]);
		$this->publishProgram($this->queue[0]);
		$this->np=$this->refreshNowplaying();
	}

	if (!$this->np->isValid()) {
		echo "R";
		$this->np=$this->refreshNowplaying();
	} else {
		echo "S";
		sleep(1);
	}
}

}

} // class

//*************************************************

if (file_exists("config.ini"))
	$config=parse_ini_file("config.ini", true);
else
	die("Configuration file config.ini is missing\n");

if ($argc!==2)
	die("Channel ID required!\n");

$pid=$argv[1];

$app=new YleNowplayingUpdater($pid, $config);
$app->run();

?>
