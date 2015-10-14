<?php

class YleException extends Exception { }
class YleAuthException extends YleException { }

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
$this->decrypt=$decrypt;
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

/*************************************************************
 *
 *************************************************************/

public function programs_items()
{
$r=$this->executeGET('programs/items.json');
return json_decode($r);
}

public function programs_services()
{
$r=$this->executeGET('programs/services.json');
return json_decode($r);
}

}

?>
