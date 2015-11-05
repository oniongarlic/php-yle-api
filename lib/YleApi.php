<?php

class YleException extends Exception { }
class YleAuthException extends YleException { }
class YleRateLimitException extends YleException { }

class YleAPIClient
{
// API Url
protected $url='https://external.api.yle.fi/v1/';

protected $app_id;
protected $app_key;
protected $decrypt_key;

protected $debug=false;

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
$r=$this->executeGET('programs/items.json');
return json_decode($r);
}

public function programs_item($id)
{
$r=$this->executeGET('programs/items/'.$id.'.json');
return json_decode($r);
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
		throw new YleException('Invalid type requested', 404);
	break;
}
$r=$this->executeGET('programs/services.json');
return json_decode($r);
}

public function programs_service($id)
{
$r=$this->executeGET('programs/services/'.$id.'.json');
return json_decode($r);
}

public function programs_nowplaying($id)
{
$r=$this->executeGET('programs/nowplaying/'.$id.'.json');
return json_decode($r);
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
$r=$this->executeGET('tracking/streamstart', $q);
return json_decode($r);
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
	'protocol'=>'HLS'
);
$r=$this->executeGET('media/playouts.json', $q);
return json_decode($r);
}

}

?>
