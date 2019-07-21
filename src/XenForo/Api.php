<?php

namespace App\XenForo;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class Api
{

    private $_client;

    private $_response;

    private $_path;

    private $_type;

    private $_base = 'http://xf2_server:80/';

    private $_key = 'IIMH6jVrrNilcyXPjExLacBzrjiJQYkB';

    private $_id = '1';

    public function __construct()
    {
        $this->_client = new Client([
            'base_uri' => $this->_base.'api/',
            'allow_redirects' => false,
            'headers' => [
                'XF-Api-Key' => $this->_key,
                'XF-Api-User' => $this->_id,
            ],
        ]);
    }

    public function setPath($_str = '')
    {
        $this->_path = $_str;
        return $this;
    }

    public function setType($_str = '')
    {
        $this->_type = strtoupper( $_str );
        return $this;
    }

    public function send($_arrData = [])
    {
        try {
            $this->_response = $this->_client->request(
                $this->_type, 
                $this->_path, 
                $this->getPayload(
                    $this->_type, 
                    $_arrData
                )
            );
        } catch (RequestException $e) {
            throw $e;
            // echo Psr7\str($e->getRequest());
            // if ($e->hasResponse()) {
            //     echo Psr7\str($e->getResponse());
            // }
            return false;
        } catch (ServerException $e) {
            throw $e;
            // echo Psr7\str($e->getRequest());
            // if ($e->hasResponse()) {
            //     echo Psr7\str($e->getResponse());
            // }
            return false;
        }
        return true;
    }

    public function getResponce()
    {
        return $this->_response;
    }

    public function getBody()
    {
        return json_decode($this->_response->getBody());
    }

    public function getCode()
    {
        return $this->_response->getStatusCode();
    }

    private function getPayload($_strMethod = '', $_arrData = [])
    {
        $_arrPayLoad = [];
        if (empty($_strMethod)) {
            return $_arrPayLoad;
        }
        if ($_strMethod == 'GET') {
            $_arrPayLoad = [
                'query' => $_arrData,
            ];
        } else {
            $_arrPayLoad = [
                'form_params' => $_arrData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]        
            ];
        }
        return $_arrPayLoad;
    }
}
