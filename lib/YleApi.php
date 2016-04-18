<?php

class YleException extends Exception { }
class YleAuthException extends YleException { }
class YleRateLimitException extends YleException { }
class YleTypeException extends YleException { }
class YleNotFoundException extends YleException { }

/**
 * YleNowPlaying
 *
 * Class to parse the interesting data from the nowplaying json structure
 *
 */
class YleNowplaying
{
private $id;
private $data;
private $count;
private $minDelta;
private $maxDelta;

private function __construct(stdClass $np)
{
$this->data=array();
foreach ($np->data as $d) {
	// Check that provided data looks like what we need
	if (!property_exists($d, 'delta'))
		throw new YleException('Invalid data delta provided');
	if (!property_exists($d, 'type'))
		throw new YleException('Invalid data type provided');
	if ($d->type!='NowPlaying')
		throw new YleException('Data is not nowplaying');

	$delta=$d->delta;
	$this->data[$delta]=array(
		'id'=>$d->content->id,
		'delta'=>$delta,
		'start'=>DateTime::createFromFormat('Y-m-d\TH:i:sT', $d->startTime),
		'end'=>DateTime::createFromFormat('Y-m-d\TH:i:sT', $d->endTime),
		'duration'=>new DateInterval($d->duration),
		'program'=>$d->partOf->title,
		'title'=>$d->content->title->unknown,
		'performer'=>$d->content->performer[0]->name
	);
}
$this->id=$np->meta->service;
$this->count=count($this->data);
$this->minDelta=min(array_keys($this->data));
$this->maxDelta=max(array_keys($this->data));
}

public function getServiceId()
{
return $this->id;
}

/**
 * isValid
 *
 * Check if the data is still relevant (last song has not been played yet)
 * This is not perfect as local time might differ from server time.
 *
 * Return: true if there are song still to be played, false if all songs have been played
 */
public function isValid()
{
$tmp=$this->data[$this->maxDelta];
$now=new DateTime();
return $tmp['end']>$now;
}

public function getCurrent()
{
$now=new DateTime();
foreach ($this->data as $d) {
	if ($d['start']<=$now && $d['end']>=$now)
		return $d;
}
return false;
}

public function get($delta)
{
if (isset($this->data[$delta]))
	return $this->data[$delta];
return false;
}

/**
 * create
 *
 * Creates an nowplaying object from the provided stdClass object from the nowplaying json data.
 *
 * Returns: An object with the nowplaying data
 */
public static function create(stdClass $np)
{
if (!property_exists($np, 'apiVersion'))
	throw new YleException('Invalid data provided');
if (!property_exists($np, 'data'))
	throw new YleException('Invalid data provided');
if (!property_exists($np, 'meta'))
	throw new YleException('Invalid data provided');
return new YleNowPlaying($np);
}

}

class YleAPIClient
{
// API Url
protected $url='https://external.api.yle.fi/v1/';

protected $app_id;
protected $app_key;
protected $decrypt_key;

protected $debug=false;

private $format='HLS'; // possible values are HLS, HDS, PMD or RTMPE
private $multibitrate=true;
private $hardsubtitles=true;

function __construct($id, $key, $decrypt)
{
$this->app_id=$id;
$this->app_key=$key;
$this->decrypt_key=$decrypt;
}

public function set_debug($bool)
{
$this->debug=$bool;
}

protected function dumpDebug($endpoint, $data=null)
{
if (!$this->debug)
	return;

printf("API Endpoint: %s\nData:\n", $endpoint);
print_r($data);
}

private function getcurl($url)
{
$curl=curl_init($url);
$header=array( 'Content-Type: application/json');
$options=array(
	CURLOPT_HEADER => FALSE,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLINFO_HEADER_OUT => TRUE,
	CURLOPT_HTTPHEADER => $header);
curl_setopt_array($curl, $options);
return $curl;
}

protected function handleStatus($status, $error, $response)
{
switch ($status) {
	case 0:
		throw new YleException($error, $status);
	case 200:
		return true;
	case 401:
		throw new YleRateLimitException($response, $status);
	case 403:
		throw new YleAuthException($response, $status);
	case 404:
		throw new YleNotFoundException($response, $status);
	default:
		throw new YleException($response, $status);
}

}

protected function executeGET($endpoint, array $query=null)
{
$url=$this->url.$endpoint;
$q=array(
	'app_id'=>$this->app_id,
	'app_key'=>$this->app_key
);
if (is_array($query))
	$q=array_merge($query, $q);

$url.='?'.http_build_query($q);

$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

$this->dumpDebug($url);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error=curl_error($curl);
curl_close($curl);

$this->handleStatus($status, $error, $response);

return $response;
}

protected function executeGETjson($endpoint, array $query=null, $assoc=false)
{
return json_decode($this->executeGET($endpoint, $query), $assoc);
}

private function getBoolString($v)
{
return $v===true ? 'true' : 'false';
}

protected function validate_pid($pid)
{
// XXX Check that input is x-yyyyy formated
return true;
}

protected function validate_mid($mid)
{
// XXX Check that input looks like a media id
return true;
}

/*************************************************************
 * Decrypt
 *************************************************************/

public function media_url_decrypt($url)
{
$tmp=base64_decode($url);
$tmp=mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->decrypt_key, substr($tmp, 16), MCRYPT_MODE_CBC, substr($tmp, 0, 16));
return substr($tmp, 0, -ord($tmp[strlen($tmp)-1]));
}

/*************************************************************
 * Programs
 *************************************************************/

public function programs_items()
{
return $this->executeGETjson('programs/items.json');
}

public function programs_item($id)
{
return $this->executeGETjson('programs/items/'.$id.'.json');
}

public function programs_services($type=false)
{
$q=array();
switch ($type) {
	case 'tvchannel':
	case 'radiochannel':
	case 'ondemandservice':
	case 'webcastservice':
		$q['type']=$type;
	break;
	case false:
		// Ignore
	break;
	default:
		throw new YleTypeException('Invalid program type requested', 404);
	break;
}
return $this->executeGETjson('programs/services.json');
}

public function programs_service($id)
{
return $this->executeGETjson('programs/services/'.$id.'.json');
}

public function programs_nowplaying($id)
{
return $this->executeGETjson('programs/nowplaying/'.$id.'.json');
}

/*************************************************************
 * Schedules
 *************************************************************/

public function schedules_now($pid=null)
{
$q=array();
if (is_string($pid))
	$q['service']=$pid;

$r=$this->executeGET('schedules/now.json', $q);
return json_decode($r);
}

/*************************************************************
 * Tracking
 *************************************************************/

public function tracking_streamstart($pid, $mid)
{
$q=array(
	'program_id'=>$pid,
	'media_id'=>$mid
);
return $this->executeGETjson('tracking/streamstart', $q);
}

/*************************************************************
 * Media
 *************************************************************/

public function media_find_ondemand_publication_media_id(stdClass $program)
{
$events=$program->data->publicationEvent;
if (count($events)==0)
	return false;
foreach ($events as $e) {
	if ($e->temporalStatus=='currently' && $e->type=='OnDemandPublication')
		return $e->media->id;
}
return false;
}

public function media_playouts($pid, $mid)
{
$q=array(
	'program_id'=>$pid,
	'media_id'=>$mid,
	'protocol'=>$this->format
);

if (is_bool($this->multibitrate))
	$q['multibitrate']=$this->getBoolString($this->multibitrate);
if (is_bool($this->hardsubtitles))
	$q['hardsubtitles']=$this->getBoolString($this->hardsubtitles);

return $this->executeGETjson('media/playouts.json', $q);
}

}

?>
