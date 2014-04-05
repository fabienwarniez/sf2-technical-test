<?php
/**
 * Created by PhpStorm.
 * User: fwarniez
 * Date: 2014-04-05
 * Time: 3:16 PM
 */

namespace Acme\FabienBundle\EventListener;

use Acme\FabienBundle\Controller\ILoginProtectedController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;

class AuthenticationListener
{
    private $container;
    private $session;
    private $router;

    public function __construct(Container $container, Router $router)
    {
        $this->container = $container;
        $this->session = $container->get('session');
        $this->router = $router;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller))
        {
            return;
        }

        if ($controller[0] instanceof ILoginProtectedController)
        {
            $loggedIn = $this->session->get('logged_in');

            if (!$loggedIn)
            {
                $redirectUrl = $this->router->generate('acme_fabien_login_index');
                $event->setController(function() use($redirectUrl) {
                    return new RedirectResponse($redirectUrl);
                });
            }
        }
    }
} 