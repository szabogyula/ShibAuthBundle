<?php

namespace Niif\ShibAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\User\UserInterface;
use Niif\ShibAuthBundle\Security\User\ShibAuthUser;

class DefaultController extends Controller
{
    public function indexAction()
    {
        dump($this->getUser());
        return $this->render('NiifShibAuthBundle:Default:index.html.twig');
    }
}
