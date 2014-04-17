<?php

namespace Acme\FabienBundle\Controller;

use Acme\FabienBundle\Service\GitHubService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/")
 */
class DefaultController extends Controller implements ILoginProtectedController
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

    /**
     * @Route("/search-results")
     * @Method("POST")
     * @Template()
     */
    public function searchResultsAction(Request $request)
    {
        $session = $this->get('session');
        $accessToken = $session->get('access_token');
        $query = $request->request->get('username');
        /** @var GitHubService $github */
        $github = $this->container->get('github');

        if (empty($query))
        {
            return $this->redirect($this->generateUrl('acme_fabien_default_index', array('error_code' => 'username_empty')));
        }

        $userRepositories = $github->getUserRepositories($query, $accessToken);

        if ($userRepositories != null)
        {
            return array(
                'logged_in_user_name' => $session->get('user_name'),
                'repos' => $userRepositories
            );
        }
        else
        {
            return $this->redirect($this->generateUrl('acme_fabien_default_index', array('error_code' => 'user_not_exist')));
        }
    }
}
