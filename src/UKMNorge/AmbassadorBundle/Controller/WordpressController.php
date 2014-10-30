<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;
use SQL;

class WordpressController extends Controller
{

    public function pageAction( $id )
    {
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['page'] = $wordpressCache->load( $id );
    	
        return $this->render('UKMAmbBundle:Wordpress:page.html.twig', $data );
    }

    public function postAction( $year, $month, $date, $id )
    {
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['post'] = $wordpressCache->load( $year.'-'.$month.'-'.$date.'-'.$id );
    	
        return $this->render('UKMAmbBundle:Wordpress:post.html.twig', $data );
    }

}