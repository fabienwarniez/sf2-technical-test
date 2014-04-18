<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-05
 * Time: 10:11 AM
 */

namespace Acme\FabienBundle\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class GitHubService
 * @package Acme\FabienBundle\Service
 */
class GitHubService
{
    /** @var INetworkRequestMaker */
    private $networkRequestMaker;

    private $clientId;
    private $clientSecret;
    private $authorizeUrl;
    private $accessTokenUrl;
    private $getLoggedInUserEndpoint;
    private $getUserRepositoriesEndpoint;

    public function __construct(INetworkRequestMaker $networkRequestMaker, $arguments)
    {
        $this->networkRequestMaker = $networkRequestMaker;

        $githubConfig = $arguments['github'];

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

        if (empty($this->clientId))
        {
            throw new InvalidConfigurationException("[GitHub] Client Id parameter not set.");
        }

        if (empty($this->clientSecret))
        {
            throw new InvalidConfigurationException("[GitHub] Client Secret parameter not set.");
        }

        if (empty($this->authorizeUrl))
        {
            throw new InvalidConfigurationException("[GitHub] Authorization URL parameter not set.");
        }

        if (empty($this->accessTokenUrl))
        {
            throw new InvalidConfigurationException("[GitHub] Access Token URL parameter not set.");
        }

        if (empty($this->getLoggedInUserEndpoint))
        {
            throw new InvalidConfigurationException("[GitHub] Logged In User endpoint parameter not set.");
        }

        if (empty($this->getUserRepositoriesEndpoint))
        {
            throw new InvalidConfigurationException("[GitHub] User Repositories endpoint parameter not set.");
        }
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
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @return string
     */
    public function getAccessToken($code, $callbackUrl)
    {
        $response = $this->networkRequestMaker->post(
            $this->accessTokenUrl,
            array(
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $callbackUrl
            ),
            array(
                'User-Agent: fabienwarniez',
                'Accept: application/json'
            )
        );

        $decodedResponse = json_decode($response, true);

        if (is_array($decodedResponse) && isset($decodedResponse['access_token']))
        {
            return $decodedResponse['access_token'];
        }
        else if (is_array($decodedResponse) && isset($decodedResponse['error']))
        {
            throw new BadRequestHttpException($decodedResponse['error'] . "\n" . $decodedResponse['error_description']);
        }
        else
        {
            throw new BadRequestHttpException("Unknown GitHub error.");
        }
    }

    /**
     * Given a valid access token, calls the GitHub API and returns the current logged in user.
     *
     * @param $accessToken string
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @return array The user object as an associative array.
     */
    public function getCurrentUser($accessToken)
    {
        $currentUserResponse = $this->networkRequestMaker->get(
            $this->getLoggedInUserEndpoint,
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
        else if (is_array($currentUserDecodedResponse) && isset($currentUserDecodedResponse['error']))
        {
            throw new BadRequestHttpException($currentUserDecodedResponse['error'] . "\n" . $currentUserDecodedResponse['error_description']);
        }
        else
        {
            throw new BadRequestHttpException("Unknown GitHub error.");
        }
    }

    /**
     * @param $userName
     * @param $accessToken
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function getUserRepositories($userName, $accessToken)
    {
        $repositoriesResponse = $this->networkRequestMaker->get(
            sprintf($this->getUserRepositoriesEndpoint, urlencode($userName)),
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
        else if (is_array($repositoriesDecodedResponse) && isset($repositoriesDecodedResponse['error']))
        {
            throw new BadRequestHttpException($repositoriesDecodedResponse['error'] . "\n" . $repositoriesDecodedResponse['error_description']);
        }
        else
        {
            throw new BadRequestHttpException("Unknown GitHub error.");
        }
    }
}
