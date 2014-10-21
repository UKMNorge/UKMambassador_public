<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use stdClass;

class WelcomeController extends Controller
{
    public function indexAction( )
    {
    	$data = $this->_prepareThemeData();
    	
        return $this->render('UKMAmbBundle:Welcome:index.html.twig', $data );
    }
    
    private function _prepareThemeData() {
	    $this->includePath = dirname(__DIR__).'/';
	    
	    $DATA = [];
	    
    	$url = new stdClass();
    	$url->theme_dir = 'http://ukm.no/wp-content/themes/UKMresponsive/';
    	$DATA['url'] = $url;
    	
    	$SEO = new stdClass();
    	$SEO->canonical 	= $this->generateUrl('ukm_amb_homepage');
    	$SEO->description 	= 'En UKM-ambassadør snakker varmt om UKM, og oppfordrer andre til å delta';
    	$SEO->author		= 'http://ukm.no/blog/author/ukm-norge/';
    	$SEO->site_name		= 'UKM for ambassadører';
    	$SEO->title			= 'Logg inn';
    	$SEO->type 			= 'login';
    	$SEO->section		= 'UKM for ambassadører';
    	$SEO->analytics		= 'UA-46216680-1';
    	$SEO->image			= 'http://grafikk.ukm.no/profil/logo/UKM-logo_stor.png';
    	
    	$DATA['SEO'] = $SEO;
    	
    	require_once('UKMconfig.inc.php');
    	define('CURRENT_UKM_DOMAIN', 'http://'. UKM_HOSTNAME);
		require_once($this->includePath. 'Resources/UKMresponsive/nav_top.controller.php');
		
		$jumbo = new stdClass();
		$jumbo->header 		= 'Logg inn';
		$jumbo->content		= 'UKM for ambassadører';
		$DATA['jumbo'] = $jumbo;
		
		return $DATA;
    }
}
