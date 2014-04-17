<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-16
 * Time: 9:42 PM
 */

namespace Acme\FabienBundle\Tests\Mocks;

use Acme\FabienBundle\Service\INetworkRequestMaker;

class CurlServiceMock implements INetworkRequestMaker
{
    const NO_RESPONSE_NEEDED = 0;
    const SUCCESSFUL_ACCESS_TOKEN = 1;
    const FAILED_ACCESS_TOKEN = 2;
    const CURRENT_USER = 3;

    // To be set by the creator of the mock based on use case
    public $mockType;

    public $lastRequestMethod;
    public $lastRequestUrl;
    public $lastRequestData;
    public $lastRequestHeaders;

    public function __construct($mockType)
    {
        $this->mockType = $mockType;
    }

    public function get($url, $headers)
    {
        $this->lastRequestMethod = "GET";
        $this->lastRequestUrl = $url;
        $this->lastRequestData = null;
        $this->lastRequestHeaders = $headers;

        return $this->responseForMockType();
    }

    public function post($url, $data, $headers)
    {
        $this->lastRequestMethod = "POST";
        $this->lastRequestUrl = $url;
        $this->lastRequestData = $data;
        $this->lastRequestHeaders = $headers;

        return $this->responseForMockType();
    }

    private function responseForMockType()
    {
        switch ($this->mockType)
        {
            case static::SUCCESSFUL_ACCESS_TOKEN:
                return "";

            case static::FAILED_ACCESS_TOKEN:
                return "";

            case static::CURRENT_USER:
                return '{"login":"fabienwarniez","name":"Fabien Warniez"}';

            default:
                return null;
        }
    }
}