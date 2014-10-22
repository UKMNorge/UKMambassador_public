<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;

class ProfileController extends Controller
{

    public function indexAction( )
    {
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
   	
        return $this->render('UKMAmbBundle:Profile:index.html.twig', $data );
    }
}