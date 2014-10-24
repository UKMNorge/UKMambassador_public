<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;
use SQL;

class JoinController extends Controller
{

    public function indexAction( )
    {
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['homepage'] = $wordpressCache->load( 'bli-ambassador/' );
    	
        return $this->render('UKMAmbBundle:Join:index.html.twig', $data );
    }
    
    public function phoneAction(Request $request) {
    	$ambassador = $this->get('ukm_amb.ambassador');

    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');
    	$data = $wordpressTheme->prepareThemeData();

	    $session = new Session();  
	    $session->set('UKMamb_phone', $request->request->get('mobil') );

		$code = $ambassador->gotInvite( $request->request->get('mobil') );
		
		if( !$code ) {
			$data['phone'] = $request->request->get('mobil');
		    return $this->render('UKMAmbBundle:Join:phoneFail.html.twig', $data );
		}
		return $this->render('UKMAmbBundle:Join:facebookConnect.html.twig', $data);		
    }
    
    public function connectAction() {
		// SERVICES
		$securityContext = $this->get('security.context');
		$ambassador = $this->get('ukm_amb.ambassador');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');
    	$session = new Session();

		// Theme data setup
    	$data = $wordpressTheme->prepareThemeData();
    	
		// Is authenticated?
		if( $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ) {
			// Whois
	   		$current_user = $this->get('security.context')->getToken()->getUser();
			
			$ambassadorObject = $ambassador->get( $current_user->getFacebookIdUnencrypted() );
					
			if( $ambassadorObject ) {
				$data['ambassador'] = $ambassadorObject;
				return $this->render('UKMAmbBundle:Join:alreadyRegistered.html.twig', $data);
			} else {
				$faceID		= $current_user->getFacebookIdUnencrypted();
				$firstname 	= $current_user->getFirstname();
				$lastname	= $current_user->getLastname();
				$phone		= $session->get('UKMamb_phone');
				$email		= $current_user->getEmail();
				$gender		= $current_user->getGender();
				$birthday	= 'n/a';
			
				$ambassadorObject = $ambassador->create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday);
				
				return $this->redirect( $this->generateUrl( 'ukm_amb_join_address' ) );
			}
		}

		return $this->render('UKMAmbBundle:Join:notAuthenticatedError.html.twig', $data );
    }
    
    public function addressAction() {
		$ambassador = $this->get('ukm_amb.ambassador');
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['homepage'] = $wordpressCache->load( 'velkomstpakke/' );

	    return $this->render('UKMAmbBundle:Join:addressForm.html.twig', $data );
    }
    
    
    public function completeAction( Request $request ) {
		// SERVICES
		$securityContext = $this->get('security.context');
		$ambassador = $this->get('ukm_amb.ambassador');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');
    	$session = new Session();

		// Theme data setup
    	$data = $wordpressTheme->prepareThemeData();
    	
		// Is authenticated?
		if( $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ) {
			// Whois
	   		$current_user = $this->get('security.context')->getToken()->getUser();
			
			$ambassadorObject = $ambassador->get( $current_user->getFacebookIdUnencrypted() );
					
			if( $ambassadorObject ) {
				$address = $request->request->get('address');
				$postalcode = $request->request->get('postalcode');
				$postalplace = $request->request->get('postalplace');

				$size = $request->request->get('size');
				
				// SETADDRESS
				$ambassador->setAddress( $ambassadorObject->getFacebookId(), $address, $postalcode, $postalplace );
				$ambassador->setSize( $ambassadorObject->getFacebookId(), $size );
				
				return $this->redirect( $this->generateUrl( 'ukm_amb_homepage' ) );
			}
		}

		return $this->render('UKMAmbBundle:Join:notAuthenticatedError.html.twig', $data );
    }

}