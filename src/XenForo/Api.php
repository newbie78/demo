<?php

namespace App\XenForo;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Api
{

    private $_client;

    private $_response;

    private $_path;

    private $_type;

    public function __construct(ParameterBagInterface $params)
    {
        $this->_client = new Client([
            'base_uri' => $params->get('xenforo.api.base').'api/',
            'allow_redirects' => false,
            'headers' => [
                'XF-Api-Key' => $params->get('xenforo.api.key'),
                'XF-Api-User' => $params->get('xenforo.api.user_id'),
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
        } catch (RequestException | ServerException $e) {
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
