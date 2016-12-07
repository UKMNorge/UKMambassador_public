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
		// Services
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');
    	$ambassadorService = $this->get('ukm_amb.ambassador');
		$securityContext = $this->get('security.context');

		if( !$securityContext->isGranted('IS_AUTHENTICATED_FULLY') ) {
			return $this->redirect( $this->generateUrl( 'ukm_amb_homepage' ) );
		}
   		$current_user = $this->get('security.context')->getToken()->getUser();
   		$current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);
   		
		// Theme data
		$data = $wordpressTheme->prepareThemeData();
    	$data['page_nav'] = $this->_nav();
    	
    	// Current profile
    	$ambassador = $ambassadorService->get( $current_user->getFacebookId() );
    	// Hvis brukeren ikke har koblet til facebook, vis facebook-innlogging.
    	// TODO: Kun redirect til join_register om brukeren ikke har fått velkomstpakke.
    	// TODO: Redirect til profile om brukeren bare logger inn som vanlig.
    	if (!$ambassador) {
    		// Denne feiler pga MariusMandalUserBundle-kødd
    		return $this->redirect($this->generateUrl('ukm_amb_join_register'));
    	}
    	$data['ambassador'] = $ambassador;
    	$data['fb_url'] = 'https://facebook.com/groups/270639562974566/';
		$data['homepage'] = $wordpressCache->load( 'hva-er-en-ambassador/' );

        return $this->render('UKMAmbBundle:Profile:index.html.twig', $data );
    }
    
    private function _nav() {
	    $navbar = [];

	    /*$nav = new stdClass();
	    $nav->id			= 'facebook';
	    $nav->url			= '//facebook.com/groups/270639562974566/';
	    $nav->title			= 'Ambassadørgruppen på facebook';
	    $nav->description	= 'Del dine erfaringer med andre ambassadører';
	    $nav->icon  		= 'face';
	    $nav->target		= '_blank';
		$navbar[] = $nav;*/

		$nav = new stdClass();
		$nav->id 			= 'facebook';
		$nav->url 			= '//facebook.com/UKMNorge';
		$nav->title 		= 'UKM på facebook';
		$nav->description 	= 'Følg UKM på facebook og del saker du bryr deg om';
		$nav->icon 			= 'face';
		$nav->target 		= '_blank';
		$navbar[] = $nav;

	    $nav = new stdClass();
	    $nav->id			= 'minnepenn';
	    $nav->url			= 'https://www.dropbox.com/sh/w6raxpg6at1iwkz/AACxZj2mvsDNXd_4xg1WaDZsa?dl=0';#$this->generateUrl( 'wordpress_page', array('id' => 'minnepenn') );
	    $nav->title			= 'Ambassadør-filer';
	    $nav->description	= 'Videoer og presentasjoner fra minnepennen';
	    $nav->icon  		= 'folder';
	    $nav->target		= '_blank';
		$navbar[] = $nav;
		
	    $nav = new stdClass();
	    $nav->id			= 'instrato';
	    $nav->url			= 'http://instrato.no/direct.php?id=213a83aeffd508fd';
	    $nav->title			= 'Grafisk profil';
	    $nav->description	= 'Logoer, skrifter, farger, videoer ++';
	    $nav->icon  		= 'palette';
	    $nav->target		= '_blank';
		$navbar[] = $nav;
		
	    $nav = new stdClass();
	    $nav->id			= 'todo';
	    $nav->url			= $this->generateUrl( 'wordpress_page', array('id' => 'hva-gjor-en-ambassador') );
	    $nav->title			= 'Hva gjør en ambassadør?';
	    $nav->description	= 'Noen enkle tips og triks';
	    $nav->icon  		= 'i';
		$navbar[] = $nav;
		
		return $navbar;
    }
}
