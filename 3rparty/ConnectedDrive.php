<?php

/**
A PHP Client for BMW Connected Drive API
Origine: https://github.com/bluewalk/BMWConnecteDrive
and modified by geqr (www.ma-maison-intelligente.fr)

Currently available functions :
- getInfo
- getNavigationInfo
- getRemoteServicesStatus
- getEfficiency

Functions to come :
- actions on car
**/

class ConnectedDrive
{
  //BMW URLs - subject to change
  private $auth_url = 'https://customer.bmwgroup.com/gcdm/oauth/authenticate';
  private $api_url = 'https://b2vapi.bmwgroup.com/api/vehicle';
  private $config = [
    'vin' => '',
    'username' => '',
    'password' => ''
  ];
  private $auth;

  private static $VEHILCE_INFO = '/dynamic/v1/%s';
  private static $REMOTESERVICES_STATUS = '/remoteservices/v1/%s/state/execution';
  private static $NAVIGATION_INFO = '/navigation/v1/%s';
  private static $EFFICIENCY = '/efficiency/v1/%s';
  private static $SERVICES = '/remoteservices/v1/%s/';
  private static $REMOTE_DOOR_LOCK= 'RDL';
  private static $REMOTE_DOOR_UNLOCK= 'RDU';
  private static $REMOTE_HORN_BLOW = "RHB";
  private static $REMOTE_LIGHT_FLASH = "RLF";
  private static $REMOTE_CLIMATE_NOW = "RCN";

  //public function  __construct($config = null) {
  public function  __construct($vin, $username, $password) {
    if (!$vin OR !$username OR !$password)
      throw new \Exception('Config parameters missing');

    $this->auth = (object) [
      'token' => '',
      'expires' => 0
    ];

    $this->_loadConfig($vin, $username, $password);

    if (file_exists(dirname(__FILE__).'/../core/config/devices/auth.json'))
      $this->auth = json_decode(file_get_contents(dirname(__FILE__).'/../core/config/devices/auth.json'));
  }

  private function _request($url, $method = 'GET', $data = null, $extra_headers = []) {
    $ch = curl_init();

    $headers = [];

    // Set token if exists
    if ($this->auth->token && $this->auth->expires > time())
      $headers[] = 'Authorization: Bearer ' . $this->auth->token;

    // Default CURL options
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Set POST/PUT data
    if ($method == 'POST' || $method == 'PUT') {
      /*if (!$data)
        throw new Exception('No data provided for POST/PUT methods');*/

      if ($this->auth->expires < time()) {
        $data_str = http_build_query($data);
      } else {
        $data_str = json_encode($data);

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($data_str);
      }

      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
    }

    // Add extra headers
    if (count($extra_headers))
      foreach ($extra_headers as $header)
        $headers[] = $header;

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute request
    $response = curl_exec($ch);

    if (!$response)
      throw new \Exception('Unable to retrieve data');

    // Get response
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($ch);

    return (object)[
      'headers' => $header,
      'body' => $body
    ];
  }

  private function _loadConfig($vin, $username, $password) {
    // working with config.json file
    //$this->config = json_decode(file_get_contents($config));
    $this->config = (object)[
      'vin' => $vin,
      'username' => $username,
      'password' => $password
    ];
  }

  private function _saveAuth() {

    file_put_contents(dirname(__FILE__).'/../core/config/devices/auth.json', json_encode($this->auth));
  }

  public function getToken() {
    $headers = [
      'Content-Type: application/x-www-form-urlencoded',
      'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_1_1 like Mac OS X) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0 Mobile/15B150 Safari/604.1'
    ];

    $data = [
      'username' => $this->config->username,
      'password' => $this->config->password,
      'client_id' => 'dbf0a542-ebd1-4ff0-a9a7-55172fbfce35',
      'response_type' => 'token',
      'scope' => 'authenticate_user fupo',
      'state' => 'eyJtYXJrZXQiOiJubCIsImxhbmd1YWdlIjoibmwiLCJkZXN0aW5hdGlvbiI6ImxhbmRpbmdQYWdlIn0',
      'locale' => 'DE-De',
      'redirect_uri' => 'https://www.bmw-connecteddrive.com/app/default/static/external-dispatch.html'
    ];
    $result = $this->_request($this->auth_url, 'POST', $data, $headers);

    if (preg_match('/.*access_token=([\w\d]+).*token_type=(\w+).*expires_in=(\d+).*/im', $result->headers, $matches)) {
      $this->auth->token = $matches[1];
      $this->auth->expires = time() + $matches[3];

      $this->_saveAuth();

      return true;
    }

    throw new \Exception('Unable to get authorization token');
  }

  private function _checkAuth() {
    if (!$this->auth->token)
      return $this->getToken();

    if ($this->auth->token && time() > $this->auth->expires)
      return $this->getToken();
  }

  public function getInfo() {

    $this->_checkAuth();
    $result = $this->_request($this->api_url . sprintf($this::$VEHILCE_INFO, $this->config->vin));
    return json_decode($result->body);
  }

  public function getRemoteServicesStatus() {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$REMOTESERVICES_STATUS, $this->config->vin), 'GET', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

  public function getNavigationInfo() {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$NAVIGATION_INFO, $this->config->vin));

    return json_decode($result->body);
  }

  public function getEfficiency()
  {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$EFFICIENCY, $this->config->vin));

    return json_decode($result->body);
  }

  public function doLightFlash ()
  {
    $this->_checkAuth();

    var_dump($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_LIGHT_FLASH);
    $result = $this->_request($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_LIGHT_FLASH, 'POST', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

  public function doClimateNow ()
  {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_CLIMATE_NOW, 'POST', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

  public function doDoorLock ()
  {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_DOOR_LOCK, 'POST', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

  public function doDoorUnlock ()
  {
    $this->_checkAuth();

    $result = $this->_request($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_DOOR_UNLOCK, 'POST', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

  public function doHornBlow ()
  {
    $this->_checkAuth();

    //var_dump($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_HORN_BLOW);
    $result = $this->_request($this->api_url . sprintf($this::$SERVICES, $this->config->vin) . $this::$REMOTE_HORN_BLOW, 'POST', null, ['Accept: application/json']);

    return json_decode($result->body);
  }

}
