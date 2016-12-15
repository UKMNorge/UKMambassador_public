<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;
use SQL;
use kommune_monstring;

class JoinController extends Controller
{

    public function indexAction( )
    {	
    	//TODO: Denne burde gå an uten å være logget inn!

    	$ambassadorService = $this->get('ukm_amb.ambassador');
    	$securityContext = $this->get('security.context');
    	$current_user = $securityContext->getToken()->getUser();
        $current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);

    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['homepage'] = $wordpressCache->load( 'bli-ambassador/' );
    	
    	$data['invite'] = null;
    	if( $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ) {
    		$invite = $ambassadorService->inviteStatus($current_user->getPhone());
		    $data['invite'] = $invite;
    	}	
        return $this->render('UKMAmbBundle:Join:index.html.twig', $data );
    }
    
    public function phoneAction(Request $request) {
    	if ( $this->container->getParameter('UKM_HOSTNAME') == 'ukm.dev') {
            $this->dipURL = 'http://delta.ukm.dev/web/app_dev.php/dip/token';
            $this->deltaLoginURL = 'http://delta.ukm.dev/web/app_dev.php/login';
        } 
        else {
            $this->dipURL = 'http://delta.ukm.no/dip/token';
            $this->deltaLoginURL = 'http://delta.ukm.no/login';
        }

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
		// It worked, now redirect somewhere else. DIP-innlogging?
		return $this->redirect($this->generateUrl('ukm_dip_login'));	
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
            $current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);
			
			$ambassadorObject = $ambassador->get( $current_user->getFacebookId() );
				
			// Found ambassador	
			if( $ambassadorObject ) {
				$data['ambassador'] = $ambassadorObject;
				return $this->render('UKMAmbBundle:Join:alreadyRegistered.html.twig', $data);
			} else {
				// Did not find ambassador, test loading by unencrypted ID (old way)
				$ambassadorObject = $ambassador->get( $current_user->getFacebookIdUnencrypted() );
				if( $ambassadorObject ) {
					$ambassadorObject->updateFacebookId( $current_user->getFacebookIdUnencrypted(), $current_user->getFacebookId() );
					// Update ambassador object with new ID
					$data['ambassador'] = $ambassadorObject;
					return $this->render('UKMAmbBundle:Join:alreadyRegistered.html.twig', $data);
				}
				
				$faceID		= $current_user->getFacebookId();
				$firstname 	= $current_user->getFirstname();
				$lastname	= $current_user->getLastname();
				$phone		= $session->get('UKMamb_phone');
				$email		= $current_user->getEmail();
				$gender		= $current_user->getGender();
				$birthday	= 'n/a';

				if( empty( $email ) ) {
					$email = 'ikke_tilgjengelig_id_'.$faceID.'@ambassador.ukm.no';
				}
			
				$ambassadorObject = $ambassador->create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday);
				
				return $this->redirect( $this->generateUrl( 'ukm_amb_join_address' ) );
			}
		}

		return $this->render('UKMAmbBundle:Join:notAuthenticatedError.html.twig', $data );
    }
    
    public function addressAction() {
        require_once('UKM/monstring_tidligere.class.php');
    	// Dette er entry-point fra DIP, så må håndtere at brukeren ikke finnes i systemet.
        $this->get('logger')->info('UKMAmbBundle:addressAction: Entered from DIP');

		$ambassadorService = $this->get('ukm_amb.ambassador');
    	$wordpressCache = $this->get('ukm_amb.wordpressCache');
    	$wordpressTheme = $this->get('ukm_amb.wordpressTheme');

    	$data = $wordpressTheme->prepareThemeData();
		$data['homepage'] = $wordpressCache->load( 'velkomstpakke/' );

    	// Current profile
   		$current_user = $this->get('security.context')->getToken()->getUser();
        $current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);

  		if( !is_object( $current_user ) ) {
	   		mail('support@ukm.no','BUG: Ambassadør-registrering', 'Kunne ikke registrere ambassadør pga feil i objekt current_user: '. var_export( $current_user, true ) );
            if( 'ukm.dev' == UKM_HOSTNAME ) {
                throw new Exception("Klart ikke å opprette bruker-objekt.");
            }
	   		return $this->render('UKMAmbBundle:Join:failCurrentUser.html.twig', $data );
   		}

        $this->get('logger')->info('UKMAmbBundle:addressAction: Laster inn ambassadør-objekt');
    	$ambassador = $ambassadorService->get( $current_user->getFacebookId() );
    	if(!$ambassador) {
            $this->get('logger')->info('UKMAmbBundle:addressAction: Fant ikke ambassadør i databasen, oppretter ny.');
            #$this->get('logger')->debug('UKMAmbBundle:addressAction: Bruker har kommune: '.$current_user->getKommuneId());
            $season = $this->get('ukm_amb.season')->getActive();
            $this->get('logger')->info('Help! Season: '.$season);
            $this->get('logger')->info('Help! k_id: '.$current_user->getKommuneId() );
            $pl = new kommune_monstring($current_user->getKommuneId(), $season);
            $monstring = $pl->monstring_get();
            $this->get('logger')->debug('UKMAmbBundle:addressAction: Mønstrings-ID: '.$monstring->get('pl_id') );

            $faceid = $current_user->getFacebookId();
            $firstname = $current_user->getFirstname();
            $lastname = $current_user->getLastname();
            $phone = $current_user->getPhone();
            $email = $current_user->getEmail();
            $gender = 'unknown';
            $bday = $current_user->getBirthdate();

            $this->get('logger')->info( 'UKMAmbBundle:addressAction: Oppretter ambassadør med følgende verdier: '.$faceid. ', '.$firstname.', '.$lastname.', '.$phone.', '.$email.', '.$gender.', '.$bday.', '.$monstring->get('pl_id') );
            $ambassador = $ambassadorService->create($faceid, $firstname, $lastname, $phone, $email, $gender, $bday, $monstring->get('pl_id'));
            $this->get('logger')->debug( 'UKMAmbBundle:addressAction: Ny ambassadør: '.var_export($ambassador, true) );
            $ambassador = $ambassadorService->get( $faceid );  
    		// Fortsatt ingen objekt, feilsjekk som ikke skal inntreffe
    		if (!$ambassador) {
    			throw new Exception('Klarte ikke å opprette ambassadør! Er du koblet til med facebook? Kontakt UKM Support.', 20006);
    		}	
    	}
    	$data['ambassador'] = $ambassador;

    	// Hvis har mottatt velkomstpakke, videresend til profile.
        // TODO: Sjekk om info er lagt inn i stedet for om skjorte er sendt? F.eks om man logger inn flere ganger i løpet av uken før skjorten er sendt?
    	if ($ambassador->getShirtSent() == 'true') {
            $this->get('logger')->info('UKMAmbBundle:addressAction: Ambassadøren har mottatt skjorte, videresendes til profilsiden.');
    		return $this->redirect( $this->generateUrl( 'ukm_amb_profile_homepage'));
    	}

        $this->get('logger')->info('UKMAmbBundle:addressAction: Ambassadøren har ikke mottatt skjorte, videresendes til adresse-skjema.');
	    return $this->render('UKMAmbBundle:Join:addressForm.html.twig', $data );
    }

    public function gotPackageAction( Request $request ) {
	    $current_user = $this->get('security.context')->getToken()->getUser();
        $current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);

    	$ambassadorService = $this->get('ukm_amb.ambassador');

    	$res = $ambassadorService->gotPackage($current_user->getFacebookId());

    	return $this->redirect( $this->generateUrl('ukm_amb_homepage') );
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
            $current_user = $this->get('dipb_user_provider')->loadUserByUsername($current_user);
			
			$ambassadorObject = $ambassador->get( $current_user->getFacebookId() );
					
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
