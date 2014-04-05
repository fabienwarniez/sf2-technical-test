<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-05
 * Time: 10:11 AM
 */

namespace Acme\FabienBundle\Services;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class GitHubService
 * @package Acme\FabienBundle\Services
 */
class GitHubService
{
    private $container;
    private $clientId;
    private $clientSecret;
    private $authorizeUrl;
    private $accessTokenUrl;
    private $getLoggedInUserEndpoint;
    private $getUserRepositoriesEndpoint;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $githubConfig = $container->getParameter('github');

        if (empty($githubConfig))
        {
            throw new InvalidConfigurationException("GitHub configuration not found.");
        }

        $this->clientId = $githubConfig['client_id'];
        $this->clientSecret = $githubConfig['client_secret'];
        $this->authorizeUrl = $githubConfig['authorize_url'];
        $this->accessTokenUrl = $githubConfig['access_token_url'];
        $this->getLoggedInUserEndpoint = $githubConfig['api_current_user_endpoint'];
        $this->getUserRepositoriesEndpoint = $githubConfig['api_user_repos_endpoint'];
    }

    /**
     * Generate the URL to redirect the user to to authorize the app on GitHub.
     *
     * @param $callbackUrl
     * @param $scope
     * @param $state
     * @return string
     */
    public function getAuthorizeUrl($callbackUrl, $scope, $state)
    {
        $url = sprintf($this->authorizeUrl, $this->clientId, urlencode($callbackUrl), $scope, $state);

        return $url;
    }

    /**
     * Makes an API call to GitHub and exchanges the specified $code for an access token.
     *
     * @param $code string The code returned by GitHub on the first step of the authentication.
     * @param $callbackUrl string
     * @return string
     */
    public function getAccessToken($code, $callbackUrl)
    {
        $response = $this->curl(
            $this->accessTokenUrl,
            'POST',
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $callbackUrl
            ),
            array('Accept: application/json')
        );

        $decodedResponse = json_decode($response, true);

        if (is_array($decodedResponse) && isset($decodedResponse['access_token']))
        {
            return $decodedResponse['access_token'];
        }
        else
        {
            return null;
        }
    }

    /**
     * Given a valid access token, calls the GitHub API and returns the current logged in user.
     *
     * @param $accessToken string
     * @return array The user object as an associative array.
     */
    public function getCurrentUser($accessToken)
    {
        $currentUserResponse = $this->curl(
            $this->getLoggedInUserEndpoint,
            'GET',
            null,
            array(
                'User-Agent: fabienwarniez',
                'Accept: application/vnd.github.beta+json',
                'Authorization: token ' . $accessToken
            )
        );
        $currentUserDecodedResponse = json_decode($currentUserResponse, true);

        if (is_array($currentUserDecodedResponse) && isset($currentUserDecodedResponse['login']))
        {
            return $currentUserDecodedResponse;
        }
        else
        {
            return null;
        }
    }

    public function getUserRepositories($userName, $accessToken)
    {
        $repositoriesResponse = $this->curl(
            sprintf($this->getUserRepositoriesEndpoint, urlencode($userName)),
            'GET',
            null,
            array(
                'User-Agent: fabienwarniez',
                'Accept: application/vnd.github.beta+json',
                'Authorization: token ' . $accessToken
            )
        );
        $repositoriesDecodedResponse = json_decode($repositoriesResponse, true);

        if (is_array($repositoriesDecodedResponse))
        {
            return $repositoriesDecodedResponse;
        }
        else
        {
            return null;
        }
    }

    /**
     * Helper method used to make HTTP requests, using CURL.
     *
     * @param $url string
     * @param $method string "GET" or "POST"
     * @param $data array Expected format: array('key1' => 'value1', 'key2' => 'value2')
     * @param $headers array
     * @return string
     */
    private function curl($url, $method, $data = null, $headers = null)
    {
        $formattedDataString = null;
        if (is_array($data) && !empty($data))
        {
            $dataStrings = array();
            foreach ($data as $key => $value)
            {
                $dataStrings []= $key . '=' . urlencode($value);
            }
            $formattedDataString = implode('&', $dataStrings);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($formattedDataString != null)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formattedDataString);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
        if (is_array($headers) && !empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
}
