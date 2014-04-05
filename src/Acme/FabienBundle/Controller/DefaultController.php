<?php

namespace Acme\FabienBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $session = $this->get('session');

        $errors = array(
            'username_empty' => 'Please enter a valid GitHub username.',
            'user_not_exist' => 'User specified does not seem to exist.'
        );

        if ($session->get('logged_in') === true)
        {
            $errorMessage = null;
            if (!is_null($request->query->get('error_code')))
            {
                $errorMessage = $errors[$request->query->get('error_code')];
            }

            return array(
                'logged_in_user_name' => $session->get('user_name'),
                'error_message' => $errorMessage
            );
        }
        else
        {
            return $this->redirect($this->generateUrl('acme_fabien_login_index'));
        }
    }

    /**
     * @Route("/search-results")
     * @Method("POST")
     * @Template()
     */
    public function searchResultsAction(Request $request)
    {
        $session = $this->get('session');
        $query = $request->request->get('username');

        if (empty($query))
        {
            return $this->redirect($this->generateUrl('acme_fabien_default_index', array('error_code' => 'username_empty')));
        }

        $gitHubParameters = $this->container->getParameter('github');
        $userReposEndpoint = $gitHubParameters['api_user_repos_endpoint'];
        $accessToken = $session->get('access_token');

        $reposResponse = $this->curl(
            sprintf($userReposEndpoint, urlencode($query)),
            'GET',
            null,
            array(
                'User-Agent: fabienwarniez',
                'Accept: application/vnd.github.beta+json',
                'Authorization: token ' . $accessToken
            )
        );
        $reposDecodedResponse = json_decode($reposResponse, true);

        $logger = $this->get('logger');
        $logger->info('decoded response: ' . print_r($reposDecodedResponse, true));

        if (is_array($reposDecodedResponse))
        {
            return array(
                'logged_in_user_name' => $session->get('user_name'),
                'repos' => $reposDecodedResponse
            );
        }
        else
        {
            return $this->redirect($this->generateUrl('acme_fabien_default_index', array('error_code' => 'user_not_exist')));
        }
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
