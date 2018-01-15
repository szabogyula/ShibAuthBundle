<?php

namespace Niif\ShibAuthBundle\Security;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class ShibbolethAuthenticator extends AbstractGuardAuthenticator implements LogoutSuccessHandlerInterface
{
    private $logger;
    private $config;
    private $tokenStorage;
    private $router;

    public function __construct($logger, $config, TokenStorage $tokenStorage, Router $router)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * Called on every request. Return whatever credentials you want,
     * or null to stop authentication.
     */
    public function getCredentials(Request $request)
    {
        $this->logger->debug('[ShibbolethAuthenticator::getCredential]');
        $shibbolethModuleAttribute = $this->config['moduleAttribute'];
        if ($request->server->has($shibbolethModuleAttribute)) {
            // What you return here will be passed to getUser() as $credentials
            $username = $request->server->get($this->config['usernameAttribute']);
            if (! $username) { // return null, there is no username in server variables
                $this->logger->debug('[ShibbolethAuthenticator::getCredential] no username in server variables');
                return null;
            }

            $retarray = array(
              'username' => $request->server->get($this->config['usernameAttribute']),
            );
            $this->logger->debug('[ShibbolethAuthenticator::getCredential] success '. var_export($retarray,1));
            return $retarray;
        } else {
            throw new AuthenticationException(
                'There is no shibboleth session, not found '
                .$shibbolethModuleAttribute
                .' key in $_SERVER array'
            );
        }
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $this->logger->debug('[ShibbolethAuthenticator::getUser]');

        // Impersonate?
        if ($this->tokenStorage->getToken()) {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                foreach ($token->getRoles() as $role) {
                    $this->logger->debug('[ShibbolethAuthenticator::getUser] role: '.get_class($role));
                    
                    if (is_a($role, 'Symfony\Component\Security\Core\Role\SwitchUserRole')) {
                        $this->logger->debug('[ShibbolethAuthenticator::getUser] Return impersonated user');
                        $token->getUser()->addRole(new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $token));
                        return $token->getUser();
                    }
                }
            }
        }

        if ($credentials !== null and is_array($credentials)) {
            $user = $userProvider->loadUserByUsername($credentials['username']);
            $this->logger->debug('[ShibbolethAuthenticator::getUser] success '.$user->getUsername());
            return $user;
        }
        $this->logger->debug('[ShibbolethAuthenticator::getUser] false return null');
        return null;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $this->logger->debug('[ShibbolethAuthenticator::checkCredentials] return true');
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->logger->debug('[ShibbolethAuthenticator::onAuthenticationSuccess]');
        // on success, let the request continue
        return;
        $session = $request->getSession();
        if ($session->has('referer')) {
            if ($session->get('referer') !== null && $session->get('referer') !== '')
            {
                $response = new RedirectResponse($session->get('referer'));
            } else {
                $response = new RedirectResponse($request->getBaseUrl());
            }
        } else {
            $response = new RedirectResponse($request->getBaseUrl());
        }
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->logger->debug('[ShibbolethAuthenticator::onAuthenticationFailure]');
        $message = $exception->getMessage();
        //$request->getSession()->invalidate();
        return new Response($message, 403);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $this->logger->debug('[ShibbolethAuthenticator::start]');
        return new RedirectResponse($this->getLoginURL());
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getLoginURL()
    {
        $currentURL = urlencode($this->getProtocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        return $this->config['baseURL'].$this->config['sessionInitiator'].'?target='.$currentURL;
    }

    private function getLogoutURL()
    {
        try {
            $returnPath = $this->router->generate($this->config['logoutReturnPath'], array(), $this->router::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $returnPath = $this->config['logoutReturnPath'];
        }
        return $this->config['baseURL'].$this->config['logoutPath'].'?return='.$returnPath;
    }

    private function getProtocol()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS'])) {
            $protocol = 'https';
        }

        return $protocol;
    }

    public function onLogoutSuccess(Request $request)
    {
        $request->getSession()->invalidate();
        return new RedirectResponse($this->getLogoutURL());
    }
}
