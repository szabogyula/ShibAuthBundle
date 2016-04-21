<?php
namespace Niif\ShibAuthBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use Niif\ShibAuthBundle\Security\User\ShibAuthUser;

class ShibAuthUserProvider implements UserProviderInterface
{
  public function loadUserByUsername($username)
  {
    $user = null;
    $shibUsername = $this->getAttribute('eppn');
    if ($username === $shibUsername) {
      $user = new ShibAuthUser(
        $this->getAttribute('eppn'),
        $this->getAttribute('mail'),
        $this->getAttribute('affilation'),
        $this->getAttribute('entitlements')
      );
    }

    return $user;
  }

  public function refreshUser(UserInterface $user)
  {
    if (!$user instanceof ShibAuthUser) {
      throw new UnsupportedUserException(
        sprintf('Instances of "%s" are not supported.', get_class($user))
      );
    }

    return $this->loadUserByUsername($user->getUsername());
  }

  public function supportsClass($class)
  {
    return $class === 'Niif\ShibAuthBundle\Security\User\User';
  }

  private function getAttribute($attributeName, $defaultValue = null) {
    return (isset($_SERVER[$attributeName])) ? $_SERVER[$attributeName] : $defaultValue;
  }
}