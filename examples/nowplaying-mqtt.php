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
$this->mqtt->setCredentials($mqtt['user'], $mqtt['pwd']);
$this->mqtt->connect($mqtt['host'], 1883);
$this->mqtt->publish('radio/nowplaying/active', 1);

$this->c=new YleApiClient($api['app_id'], $api['app_key'], $api['decrypt']);
}

private function getChannelTopic($broadcaster, $id, $offset, $item)
{
// radio/yle/x3m/0/item
return sprintf('radio/%s/%s/%d/%s', $broadcaster, $id, $offset, $item);
}

private function publishSong($delta, array $data=null)
{
$tmp=array(
	'id'=>$data['id'],
	's'=>$data['start']->format('Y-m-d H:i:s'),
	'e'=>$data['end']->format('Y-m-d H:i:s'),
	't'=>$data['title'],
	'p'=>$data['performer']
	);
echo "DELTA: $delta\n"; print_r($tmp);
if (is_array($data)) {
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'json'), json_encode($tmp), 1, true);
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'title'), $data['title'], 1, true);
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'performer'), $data['performer'], 1, true);
} else {
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'json'), "", 1, true);
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'title'), "", 1, true);
	$this->mqtt->publish($this->getChannelTopic('yle', $this->pid, $delta, 'performer'), "", 1, true);
}
return true;
}

private function publishProgram(array $data=null)
{
$topic=$this->getChannelTopic('yle', $this->pid, 0, 'program');
if (is_array($data))
	$r=$this->mqtt->publish($topic, $data['program']->sv, 1, true);
else
	$r=$this->mqtt->publish($topic, "", 1, true);
return $r;
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
	else
		$this->publishSong($delta, NULL);
}

while (true) {
	echo ".";
	$this->mqtt->loop(4000);

	$cnt++;

	// Check current
	$nc=$this->np->getCurrent();

	if ($nc==false && $this->np->isValid()) {
		// XXX: Check time to next song
		echo "P2";
		sleep(2);
		continue;
	} else if ($nc!==false) {
		echo "UP";
		$pcur=$this->queue[0];
		$this->queue[0]==$nc;
		//$this->updateNext(0);
	} else {
		echo "S1";
		sleep(1);
		continue;
	}

	if ($this->queue[0]===false && $this->np->isValid()) {
		echo "N";
		$this->updateNext(1);
		$this->updateNext(2);
	} else if ($this->queue[0]!==false && $this->queue[0]['id']!=$pcur['id']) {
		echo "C";
		$this->queue[-1]=$pcur;
		$this->updateNext(-1);
		$this->updateNext(0);
		$this->publishProgram($this->queue[0]);
		$this->np=$this->refreshNowplaying();
		continue;
	}

	echo "S2";
	sleep(2);
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
