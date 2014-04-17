<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-16
 * Time: 8:11 PM
 */

namespace Acme\FabienBundle\Service;

/**
 * Interface INetworkRequestMaker
 * @package Acme\FabienBundle\Service
 */
interface INetworkRequestMaker
{
    public function get($url, $headers);
    public function post($url, $data, $headers);
}