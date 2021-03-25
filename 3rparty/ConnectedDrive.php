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
    const AUTH_URL = 'https://customer.bmwgroup.com/gcdm/oauth/authenticate';
    const API_URL = 'https://b2vapi.bmwgroup.com/api/vehicle';
    const VEHILCE_INFO = '/dynamic/v1/%s';
    const REMOTESERVICES_STATUS = '/remoteservices/v1/%s/state/execution';
    const NAVIGATION_INFO = '/navigation/v1/%s';
    const EFFICIENCY = '/efficiency/v1/%s';
    const SERVICES = '/remoteservices/v1/%s/';
    const MESSAGES = '/myinfo/v1';
    const REMOTE_DOOR_LOCK= 'RDL';
    const REMOTE_DOOR_UNLOCK= 'RDU';
    const REMOTE_HORN_BLOW = "RHB";
    const REMOTE_LIGHT_FLASH = "RLF";
    const REMOTE_CLIMATE_NOW = "RCN";
    const ERROR_CODE_MAPPING = [
        200 => 'OK',
        401 => 'UNAUTHORIZED',
        404 => 'NOT_FOUND',
        405 => 'MOBILE_ACCESS_DISABLED',
        408 => 'VEHICLE_UNAVAILABLE',
        423 => 'ACCOUNT_LOCKED',
        429 => 'TOO_MANY_REQUESTS',
        500 => 'SERVER_ERROR',
        503 => 'SERVICE_MAINTENANCE',
    ];

    /** @var Config $config  */
    private $config = null;
    /** @var Auth $auth  */
    private $auth = null;

    public function  __construct($vin, $username, $password)
    {
        if (!$vin || !$username || !$password) {
            throw new \Exception('Config parameters missing');
        }

        $this->auth = new Auth('', 0);

        $this->_loadConfig($vin, $username, $password);

        if (file_exists(dirname(__FILE__).'/../core/config/devices/auth.json')) {
            $auth = json_decode(file_get_contents(dirname(__FILE__).'/../core/config/devices/auth.json'), true);
            $this->auth->setExpires($auth['expires']);
            $this->auth->setToken($auth['token']);
        }
    }

    private function _request($url, $method = 'GET', $data = null, $extra_headers = [])
    {
        $ch = curl_init();

        $headers = [];

        // Set token if exists
        if ($this->auth->getToken() && $this->auth->getExpires() > time()) {
            $headers[] = 'Authorization: Bearer ' . $this->auth->getToken();
        }

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
        if (in_array($method, ['POST', 'PUT'])) {
            /*if (!$data)
              throw new Exception('No data provided for POST/PUT methods');*/

            if ($this->auth->getExpires() < time()) {
                $data_str = http_build_query($data);
            } else {
                $data_str = json_encode($data);

                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: ' . strlen($data_str);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
        }

        // Add extra headers
        if (count($extra_headers)) {
            foreach ($extra_headers as $header) {
                $headers[] = $header;
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute request
        $response = curl_exec($ch);

        if (!$response) {
            throw new \Exception('Unable to retrieve data');
        }

        // Get response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);

        return (object)[
            'headers' => $header,
            'body' => json_decode($body),
            'httpCode' => $this->_convertHttpCode($httpCode)
        ];
    }

    private function _loadConfig($vin, $username, $password)
    {
        // working with config.json file
        //$this->config = json_decode(file_get_contents($config));
        $this->config = new Config($vin, $username, $password);
    }

    private function _saveAuth()
    {
        file_put_contents(dirname(__FILE__).'/../core/config/devices/auth.json', json_encode($this->auth));
    }

    public function getToken()
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_1_1 like Mac OS X) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0 Mobile/15B150 Safari/604.1'
        ];

        $data = [
            'username' => $this->config->getUsername(),
            'password' => $this->config->getPassword(),
            'client_id' => 'dbf0a542-ebd1-4ff0-a9a7-55172fbfce35',
            'response_type' => 'token',
            'scope' => 'authenticate_user fupo',
            'state' => 'eyJtYXJrZXQiOiJubCIsImxhbmd1YWdlIjoibmwiLCJkZXN0aW5hdGlvbiI6ImxhbmRpbmdQYWdlIn0',
            'locale' => 'DE-De',
            'redirect_uri' => 'https://www.bmw-connecteddrive.com/app/default/static/external-dispatch.html'
        ];
        $result = $this->_request(self::AUTH_URL, 'POST', $data, $headers);

        if (preg_match('/.*access_token=([\w\d]+).*token_type=(\w+).*expires_in=(\d+).*/im', $result->headers, $matches)) {
            $this->auth->setToken($matches[1]);
            $this->auth->setExpires(time() + $matches[3]);

            $this->_saveAuth();

            return true;
        }

        throw new \Exception('Unable to get authorization token');
    }

    private function _checkAuth()
    {
        if (!$this->auth->getToken() || time() > $this->auth->getExpires()) {
            return $this->getToken();
        }
        return false;
    }

    private function _convertHttpCode($code)
    {
        return sprintf('%s - %s', $code, self::ERROR_CODE_MAPPING[$code]);
    }

    public function getInfo()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::VEHILCE_INFO, $this->config->getVin()));
    }

    public function getRemoteServicesStatus()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::REMOTESERVICES_STATUS, $this->config->getVin()), 'GET', null, ['Accept: application/json']);
    }

    public function getNavigationInfo()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::NAVIGATION_INFO, $this->config->getVin()));
    }

    public function getEfficiency()
    {
        $this->_checkAuth();

        $result = $this->_request(self::API_URL . sprintf(self::EFFICIENCY, $this->config->getVin()));

        return $result;
    }

    public function doLightFlash()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::SERVICES, $this->config->getVin()) . self::REMOTE_LIGHT_FLASH, 'POST', null, ['Accept: application/json']);
    }

    public function doClimateNow()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::SERVICES, $this->config->getVin()) . self::REMOTE_CLIMATE_NOW, 'POST', null, ['Accept: application/json']);
    }

    public function doDoorLock()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::SERVICES, $this->config->getVin()) . self::REMOTE_DOOR_LOCK, 'POST', null, ['Accept: application/json']);
    }

    public function doDoorUnlock()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::SERVICES, $this->config->getVin()) . self::REMOTE_DOOR_UNLOCK, 'POST', null, ['Accept: application/json']);
    }

    public function doHornBlow()
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . sprintf(self::SERVICES, $this->config->getVin()) . self::REMOTE_HORN_BLOW, 'POST', null, ['Accept: application/json']);
    }

    public function doSendMessage($title, $message)
    {
        $this->_checkAuth();

        return $this->_request(self::API_URL . self::MESSAGES, 'POST', ["vins"=>[$this->config->getVin()], "message" => $message, "subject" => $title], ['Accept: application/json']);
    }
}
