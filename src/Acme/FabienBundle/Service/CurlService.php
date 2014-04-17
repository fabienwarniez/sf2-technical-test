<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-05
 * Time: 10:11 AM
 */

namespace Acme\FabienBundle\Service;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class CurlService
 * @package Acme\FabienBundle\Service
 */
class CurlService implements INetworkRequestMaker
{
    /**
     * Helper method used to make GET HTTP requests.
     *
     * @param $url string
     * @param $headers array
     * @return string
     */
    public function get($url, $headers = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (is_array($headers) && !empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * Helper method used to make POST HTTP requests.
     *
     * @param $url
     * @param $data array Expected format: array('key1' => 'value1', 'key2' => 'value2')
     * @param array $headers
     * @return mixed
     */
    public function post($url, $data, $headers = null)
    {
        $formattedDataString = null;
        if (is_array($data) && !empty($data))
        {
            $formattedDataString = http_build_query($data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($formattedDataString != null)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formattedDataString);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (is_array($headers) && !empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}
