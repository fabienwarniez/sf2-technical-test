<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-04
 * Time: 7:23 PM
 */

namespace Acme\FabienBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;

const GITHUB_STATE_SESSION_KEY = 'github_state';

/**
 * @Route("/login")
 */
class LoginController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/authenticate")
     */
    public function authenticateAction()
    {
        $session = $this->get('session');

        $gitHubParameters = $this->container->getParameter('github');
        $clientId = $gitHubParameters['client_id'];
        $authorizeUrl = $gitHubParameters['authorize_url'];

        $redirectUri = $this->generateUrl('acme_fabien_login_callback', array(), true);
        $scope = ""; // default scope is enough to list public repositories of any user
        $state = uniqid('github');
        $session->set('github_state', $state);
        $url = sprintf($authorizeUrl, $clientId, urlencode($redirectUri), $scope, $state);

        return $this->redirect($url);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $session = $this->get('session');

        $submittedState = $session->get('github_state');
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (empty($code) || empty($state))
        {
            throw new InvalidArgumentException("The information returned by GitHub is incomplete.");
        }

        if ($submittedState != $state)
        {
            throw new AccessDeniedException("This browser did not initiate the GitHub login request.");
        }

        // reset state session variable
        $session->set('github_state', null);

        $gitHubParameters = $this->container->getParameter('github');
        $clientId = $gitHubParameters['client_id'];
        $clientSecret = $gitHubParameters['client_secret'];
        $accessTokenUrl = $gitHubParameters['access_token_url'];
        $currentUserEndpoint = $gitHubParameters['api_current_user_endpoint'];

        $response = $this->curl(
            $accessTokenUrl,
            'POST',
            array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $this->generateUrl('acme_fabien_login_callback', array(), true)
            ),
            array('Accept: application/json')
        );

        $decodedResponse = json_decode($response, true);

        if (is_array($decodedResponse) && isset($decodedResponse['access_token']))
        {
            $accessToken = $decodedResponse['access_token'];
            $session->set('logged_in', true);
            $session->set('access_token', $accessToken);

            $currentUserResponse = $this->curl(
                $currentUserEndpoint,
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
                $session->set('user_name', $currentUserDecodedResponse['login']);
            }
            else
            {
                throw new AccessDeniedException("User information could not be retrieved.");
            }

            return $this->redirect($this->generateUrl('acme_fabien_login_success'));
        }
        else
        {
            return $this->redirect($this->generateUrl('acme_fabien_login_failed'));
        }
    }

    /**
     * @Route("/success")
     * @Template()
     */
    public function successAction()
    {
        return array();
    }

    /**
     * @Route("/failed")
     * @Template()
     */
    public function failedAction()
    {
        return array();
    }

    /**
     * @Route("/logout")
     */
    public function logoutAction()
    {
        $session = $this->get('session');

        $session->set('logged_in', false);
        $session->set('access_token', null);
        $session->set('user_name', null);

        return $this->redirect($this->generateUrl('acme_fabien_default_index'));
    }

    /**
     * @param $url string
     * @param $method string "GET" or "POST"
     * @param $data array Expected format: array('key1' => 'value1', 'key2' => 'value2')
     * @param $headers array
     * @return string
     */
    private static function curl($url, $method, $data = null, $headers = null)
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