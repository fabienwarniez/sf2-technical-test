<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-16
 * Time: 8:34 PM
 */

namespace Acme\FabienBundle\Tests\Service;


use Acme\FabienBundle\Service\CurlService;
use Acme\FabienBundle\Service\GitHubService;
use Acme\FabienBundle\Tests\Mocks\CurlServiceMock;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class GitHubServiceTest extends TestCase
{
    public $gitHubConfig;

    private function setupConfig()
    {
        $this->gitHubConfig = array(
            'client_id' => 'abc123',
            'client_secret' => 'feogiwmeoiw3492ji',
            'authorize_url' => 'https://github.com/login/oauth/authorize?client_id=%s&redirect_uri=%s&scope=%s&state=%s',
            'access_token_url' => 'https://github.com/login/oauth/access_token',
            'api_current_user_endpoint' => 'https://api.github.com/user',
            'api_user_repos_endpoint' => 'https://api.github.com/users/%s/repos'
        );
    }

    public function testAuthorizeUrl()
    {
        $this->setupConfig();

        $curlServiceMock = new CurlServiceMock(CurlServiceMock::NO_RESPONSE_NEEDED);
        $gitHubService = new GitHubService($curlServiceMock, array('github' => $this->gitHubConfig));

        $callBackUrl = "callback_url";
        $scope = "custom_scope";
        $state = "123";

        $authorizeUrl = $gitHubService->getAuthorizeUrl($callBackUrl, $scope, $state);

        $this->assertEquals('https://github.com/login/oauth/authorize?client_id=' . $this->gitHubConfig['client_id'] . '&redirect_uri=' . $callBackUrl . '&scope=' . $scope . '&state=' . $state, $authorizeUrl);
    }

    public function testGetCurrentUser()
    {
        $this->setupConfig();

        $curlServiceMock = new CurlServiceMock(CurlServiceMock::CURRENT_USER);
        $gitHubService = new GitHubService($curlServiceMock, array('github' => $this->gitHubConfig));

        $accessToken = "789fgh";

        $currentUser = $gitHubService->getCurrentUser($accessToken);

        $this->assertEquals("Fabien Warniez", $currentUser['name']);
        $this->assertEquals("GET", $curlServiceMock->lastRequestMethod);
        $this->assertEquals($this->gitHubConfig['api_current_user_endpoint'], $curlServiceMock->lastRequestUrl);
    }
}