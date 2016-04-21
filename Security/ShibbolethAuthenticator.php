<?php

namespace Niif\ShibAuthBundle\Security;

use Niif\ShibAuthBundle\Security\User\ShibAuthUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ShibbolethAuthenticator extends AbstractGuardAuthenticator
{

  /**
   * Called on every request. Return whatever credentials you want,
   * or null to stop authentication.
   */
  public function getCredentials(Request $request)
  {

    if (!$request->server->get('Shib-Identity-Provider')) {
      // no token? Return null and no other methods will be called
      return;
    }

    // What you return here will be passed to getUser() as $credentials
    if ($request->server->get('eppn') AND $request->server->get('mail'))
    return array(
      'userName'      => $request->server->get('eppn'),
      'mail'          => $request->server->get('mail'),
      'affiliations'   => $request->server->get('affiliation'),
      'entitlements'  => $request->server->get('entitlement'),
    );
  }

  public function getUser($credentials, UserProviderInterface $userProvider)
  {
    if ($credentials !== NULL AND is_array($credentials)) {
      $user = new ShibAuthUser(
        $credentials['userName'],
        $credentials['mail'],
        $credentials['affiliations'],
        $credentials['entitlements']
      );
    }
    else {
      $user = '';
    }
    // if null, authentication will fail
    // if a User object, checkCredentials() is called
    return $user;
  }

  public function checkCredentials($credentials, UserInterface $user)
  {
    // check credentials - e.g. make sure the password is valid
    // no credential check is needed in this case

    // return true to cause authentication success
    return true;
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
  {
    // on success, let the request continue
    return null;
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    $data = array(
      'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

      // or to translate this message
      // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
    );

    $request->getSession()->invalidate();
    return new JsonResponse($data, 403);
  }

  /**
   * Called when authentication is needed, but it's not sent
   */
  public function start(Request $request, AuthenticationException $authException = null)
  {
    $data = '<a href="https://dev.aai.niif.hu/Shibboleth.sso/DSS?target=https%3A%2F%2Fdev.aai.niif.hu%2Fdocument_manager%2Fweb%2Fapp_dev.php">Shib Login</a><br />';
    //$data = $this->generateLoginURL();
    $request->getSession()->invalidate();
    return new Response($data, 401);
  }

  public function supportsRememberMe()
  {
    return false;
  }

  public function generateLoginURL($linkText = 'Shibboleth Login') {
    //retrun $this->generateUrl($linktext, 'https://dev.aai.niif.hu/Shibboleth.sso/DSS?target=https%3A%2F%2Fdev.aai.niif.hu%2Fdocument_manager%2Fweb%2Fapp_dev.php', UrlGeneratorInterface::ABSOLUTE_URL);
  }
}