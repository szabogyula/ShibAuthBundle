<?php

namespace Niif\ShibAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('NiifShibAuthBundle:Default:index.html.twig');
    }
}
