<?php

namespace UKMNorge\DipBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => $name));
    }
}
