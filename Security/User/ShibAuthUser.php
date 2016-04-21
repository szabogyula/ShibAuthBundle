<?php
namespace Niif\ShibAuthBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

class ShibAuthUser implements UserInterface
{

  private $username;
  private $mail;
  private $affiliations;
  private $entitlements;
  
  public function __construct($username, $mail, $affiliations, $entitlements) {
    $this->username = $username;
    $this->mail = $mail;
    $this->affiliations = $affiliations;
    $this->entitlements = $entitlements;
  }

  /**
   * @return mixed
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @param mixed $username
   */
  public function setUsername($username) {
    $this->username = $username;
  }


  /**
   * @return mixed
   */
  public function getMail() {
    return $this->mail;
  }

  /**
   * @param mixed $mail
   */
  public function setMail($mail) {
    $this->mail = $mail;
  }

  /**
   * @return mixed
   */
  public function getAffiliations() {
    return $this->affiliations;
  }

  /**
   * @param mixed $affiliations
   */
  public function setAffiliations($affiliations) {
    $this->affiliations = $affiliations;
  }

  /**
   * @return mixed
   */
  public function getEntitlements() {
    return $this->entitlements;
  }

  /**
   * @param mixed $entitlements
   */
  public function setEntitlements($entitlements) {
    $this->entitlements = $entitlements;
  }

  public function getRoles()
  {
    return ['ROLE_USER'];
  }

  public function getPassword()
  {
  }

  public function getSalt()
  {
  }

  public function eraseCredentials()
  {
  }
}