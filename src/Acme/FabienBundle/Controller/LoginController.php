<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-04
 * Time: 7:23 PM
 */

namespace Acme\FabienBundle\Controller;

use Acme\FabienBundle\Service\GitHubService;
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
        /** @var GitHubService $github */
        $github = $this->container->get('github');

        $redirectUri = $this->generateUrl('acme_fabien_login_callback', array(), true);
        $scope = ""; // default scope is enough to list public repositories of any user
        $state = uniqid('github');
        $session->set('github_state', $state);
        $url = $github->getAuthorizeUrl($redirectUri, $scope, $state);

        return $this->redirect($url);
    }

    /**
     * @Route("/callback")
     */
    public function callbackAction(Request $request)
    {
        $session = $this->get('session');
        /** @var GitHubService $github */
        $github = $this->container->get('github');

        $submittedState = $session->get('github_state');
        $session->set('github_state', null);

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

        $callbackUrl = $this->generateUrl('acme_fabien_login_callback', array(), true);
        $accessToken = $github->getAccessToken($code, $callbackUrl);

        if ($accessToken != null)
        {
            $session->set('logged_in', true);
            $session->set('access_token', $accessToken);

            $currentUser = $github->getCurrentUser($accessToken);

            if ($currentUser != null)
            {
                $session->set('user_name', $currentUser['login']);
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

        return $this->redirect($this->generateUrl('acme_fabien_login_index'));
    }
}