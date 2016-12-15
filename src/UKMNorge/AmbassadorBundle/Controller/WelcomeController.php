<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;

class WelcomeController extends Controller
{

    public function indexAction( )
    {
		// If logged in or currently registering, forward to correct route
		$redirect = $this->_nextStep();
		if( $redirect ) {
			return $redirect;
		}
		
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();

		$securityContext = $this->get('security.context');
		$data['authenticated'] = $securityContext->isGranted('IS_AUTHENTICATED_FULLY');
		$data['homepage'] = $wordpressCache->load( 'hva-er-en-ambassador/' );

    	
        return $this->render('UKMAmbBundle:Welcome:index.html.twig', $data );
    }
    
    private function _nextStep() {
		// Services ++
		$securityContext = $this->get('security.context');
		$ambassador = $this->get('ukm_amb.ambassador');
		
		// Is authenticated?
		if( $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ) {
			// Whois
	   		$current_user = $this->get('security.context')->getToken()->getUser();
	   		$current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);
			
			$ambassadorObject = $ambassador->get( $current_user->getFacebookId() );
			
		    $session = new Session();  
			$phone = $session->get('UKMamb_phone');
			
			if( !$ambassadorObject ) {
				// Did not find ambassador, test loading by unencrypted ID (old way)
				$ambassadorObject = $ambassador->get( $current_user->getFacebookIdUnencrypted() );
				if( $ambassadorObject ) {
					$ambassadorObject->updateFacebookId( $current_user->getFacebookIdUnencrypted(), $current_user->getFacebookId() );
				}
			}
			
			if( $ambassadorObject ) {
				return $this->redirect( $this->generateUrl( 'ukm_amb_profile_homepage' ) ); #, array('ID' => $ambassadorObject->getId() ) ) );
			} elseif( !empty( $phone ) ) {
				return $this->redirect( $this->generateUrl( 'ukm_amb_join_register' ) );
			}
		}
		// No need to redirect
		return false;
    }
}