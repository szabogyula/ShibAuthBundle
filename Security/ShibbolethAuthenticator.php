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

/**
 * Class ShibbolethAuthenticator
 *
 * @package Niif\ShibAuthBundle\Security
 * @author Gyula SZABO <gyufi@eduid.hu>
 */
class ShibbolethAuthenticator extends AbstractGuardAuthenticator implements LogoutSuccessHandlerInterface
{
    /**
     * @var
     */
    private $logger;
    /**
     * @var
     */
    private $config;
    /**
     * @var TokenStorage
     */
    private $tokenStorage;
    /**
     * @var Router
     */
    private $router;

    /**
     * ShibbolethAuthenticator constructor.
     *
     * @param              $logger
     * @param              $config
     * @param TokenStorage $tokenStorage
     * @param Router       $router
     */
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
     *
     */
    public function getCredentials(Request $request)
    {
        $this->logger->debug('[ShibbolethAuthenticator::getCredential]');
        $shibbolethModuleAttribute = $this->config['moduleAttribute'];
        if ($request->server->has($shibbolethModuleAttribute)) {
            // What you return here will be passed to getUser() as $credentials
            $username = $request->server->get($this->config['usernameAttribute']);
            if (!$username) { // return null, there is no username in server variables
                $this->logger->debug('[ShibbolethAuthenticator::getCredential] no username in server variables');

                return null;
            }

            $retarray = array(
                'username' => $request->server->get($this->config['usernameAttribute']),
            );
            $this->logger->debug('[ShibbolethAuthenticator::getCredential] success ' . var_export($retarray, 1));

            return $retarray;
        } else {
            $this->logger->error('[ShibbolethAuthenticator::getCredential] no moduleAttribute '.$shibbolethModuleAttribute.' is not found in $_SERVER array: ' . var_export($_SERVER, 1));
            throw new AuthenticationException(
                'There is no shibboleth session, not found '
                . $shibbolethModuleAttribute
                . ' key in $_SERVER array'
                . var_export($_SERVER, 1)
            );
        }
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return mixed|null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $this->logger->debug('[ShibbolethAuthenticator::getUser]');

        // Impersonate?
        if ($this->tokenStorage->getToken()) {
            $token = $this->tokenStorage->getToken();
            if ($token) {
                foreach ($token->getRoles() as $role) {
                    $this->logger->debug('[ShibbolethAuthenticator::getUser] role: ' . get_class($role));

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
            $this->logger->debug('[ShibbolethAuthenticator::getUser] success ' . $user->getUsername());

            return $user;
        }
        $this->logger->debug('[ShibbolethAuthenticator::getUser] false return null');

        return null;
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $this->logger->debug('[ShibbolethAuthenticator::checkCredentials] return true');
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse|void
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->logger->debug('[ShibbolethAuthenticator::onAuthenticationSuccess]');

        // on success, let the request continue
        return;
        $session = $request->getSession();
        if ($session->has('referer')) {
            if ($session->get('referer') !== null && $session->get('referer') !== '') {
                $response = new RedirectResponse($session->get('referer'));
            } else {
                $response = new RedirectResponse($request->getBaseUrl());
            }
        } else {
            $response = new RedirectResponse($request->getBaseUrl());
        }

        return $response;
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->logger->debug('[ShibbolethAuthenticator::onAuthenticationFailure]');
        $message = $exception->getMessage();

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

    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * @return string
     */
    private function getLoginURL()
    {
        $currentURL = urlencode($this->getProtocol() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

        return $this->config['baseURL'] . $this->config['sessionInitiator'] . '?target=' . $currentURL;
    }

    /**
     * @return string
     */
    private function getLogoutURL()
    {
        try {
            $router = $this->router;
            $returnPath = $this->router->generate($this->config['logoutReturnPath'], array(), $router::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $returnPath = $this->config['logoutReturnPath'];
        }

        return $this->config['baseURL'] . $this->config['logoutPath'] . '?return=' . $returnPath;
    }

    /**
     * @return string
     */
    private function getProtocol()
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS'])) {
            $protocol = 'https';
        }

        return $protocol;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $request->getSession()->invalidate();

        return new RedirectResponse($this->getLogoutURL());
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return true;
    }

}
