<?php

namespace UKMNorge\AmbassadorBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use stdClass;
use Exception;
use SimpleXMLElement;
use UKMCURL;

class WordpressThemeService
{
    /**
     *
     * @var ContainerInterface 
     */
    protected $container;

	/**
	 * 
	 * Class constructor
	 * @param ContainerInterface
	 *
	*/
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
    
    public function prepareThemeData() {
    	require_once('UKMconfig.inc.php');

	    $this->includePath = dirname(__DIR__).'/';
	    
	    $DATA = [];
	    
    	$url = new stdClass();
    	$url->theme_dir = 'http://ukm.no/wp-content/themes/UKMresponsive/';
    	$url->site	= 'http://ambassador.'. UKM_HOSTNAME .'/';
    	$DATA['url'] = $url;

    	define('CURRENT_UKM_DOMAIN', 'http://'. UKM_HOSTNAME);
		require_once($this->includePath. 'Resources/UKMresponsive/nav_top.controller.php');
		
		$jumbo = new stdClass();
		$jumbo->header 		= 'Ambassadørsiden';
		$jumbo->content		= 'Logg inn';
		$DATA['jumbo'] = $jumbo;
		
		$placeholder = new stdClass();
		$placeholder->post = 'http://grafikk.ukm.no/placeholder/post_placeholder.png';
		$DATA['placeholder'] = $placeholder;

		return $DATA;
    }
}