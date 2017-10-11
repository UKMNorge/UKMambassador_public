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
    	
    	// Current profile
    	$ambassador = $ambassadorService->get( $current_user->getFacebookId() );
    	// Hvis brukeren ikke har koblet til facebook, vis facebook-innlogging.
    	// TODO: Kun redirect til join_register om brukeren ikke har fått velkomstpakke.
    	// TODO: Redirect til profile om brukeren bare logger inn som vanlig.
    	if (!$ambassador) {
    		// Denne feiler pga MariusMandalUserBundle-kødd
    		return $this->redirect($this->generateUrl('ukm_amb_join_register'));
    	}
    	
    	if( $ambassador->isDeaktivert() ) {
	    	$ambassador->aktiver();
    	}
    	
    	$data['ambassador'] = $ambassador;
    	$data['fb_url'] = 'https://facebook.com/groups/270639562974566/';
		$data['homepage'] = $wordpressCache->load( 'hva-er-en-ambassador/' );

        return $this->render('UKMAmbBundle:Profile:index.html.twig', $data );
    }
}
