<?php

namespace UKMNorge\DipBundle\Controller;

// For å kunne dele sessions på flere sider
#ini_set('session.cookie_domain', '.ukm.dev' );

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use HttpRequest;
use Symfony\Component\HttpFoundation\Request;
use UKMNorge\DipBundle\Entity\Token;
use UKMNorge\DipBundle\Entity\User;
use UKMCurl;
use Exception;
use DateTime;
use UKMNorge\DipBundle\Security\Provider\DipBUserProvider;

class TokenController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => $name));
    }

    public function loginAction() 
    {	
        if ( $this->container->getParameter('UKM_HOSTNAME') == 'ukm.dev') {
            $this->dipURL = 'https://delta.ukm.dev/web/app_dev.php/dip/token';
            $this->deltaLoginURL = 'https://delta.ukm.dev/web/app_dev.php/login';
        } 
        else {
            $this->dipURL = 'https://delta.ukm.no/dip/token';
            $this->deltaLoginURL = 'https://delta.ukm.no/login';
        }

    	require_once('UKM/curl.class.php');
    	// Dette er entry-funksjonen til DIP-innlogging.
    	// Her sjekker vi om brukeren har en session med en autentisert token, 
    	// og hvis ikke genererer vi en og sender brukeren videre til Delta.

    	// Send request to Delta with token-info
        $location = $this->container->getParameter('dip_location');
        $firewall_name = $this->container->getParameter('dip_firewall_area');
        $entry_point = $this->container->getParameter('dip_entry_point');
    	$curl = new UKMCurl();

    	// Har brukeren en session med token?
    	$session = $this->get('session');
    	if ($session->isStarted()) {
    		$token = $session->get('token');
    		if ($token) {
    			// Hvis token finnes, sjekk at det er autentisert i databasen
    			$repo = $this->getDoctrine()->getRepository('UKMDipBundle:Token');
    			$existingToken = $repo->findOneBy(array('token' => $token));
    	
    			if ($existingToken) {
    				// Hvis token finnes
    				if ($existingToken->getAuth() == true) {
    					// Authorized, so trigger log in
    					$userId = $existingToken->getUserId();

    					// Load user data?
    					$userProvider = $this->get('dipb_user_provider');
    					$user = $userProvider->loadUserByUsername($userId);
				        $token = new UsernamePasswordToken($user, $user->getPassword(), $firewall_name, $user->getRoles());

				        // For older versions of Symfony, use security.context here
                        // Newer uses security.token_storage
				        $this->get("security.context")->setToken($token);

				        // Fire the login event
				        // Logging the user in above the way we do it doesn't do this automatically
				        //now dispatch the login event
					    $request = $this->get("request");
				        $event = new InteractiveLoginEvent($request, $token);
				        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

				        // Redirect til en side bak firewall i stedet
				        return $this->redirect($this->generateUrl($entry_point));
    				}
    				else {
    					// Hvis token ikke er autentisert enda
    					// Fjern lagret token
    					$session->invalidate();
                        return $this->redirect($this->get('router')->generate('ukm_dip_login'));
    				}
    			}
    			// Token finnes, men ikke i databasen.
    			// Ingen token som matcher, ugyldig?
    			// Genererer ny og last inn siden på nytt?
                // Denne burde ikke dukke opp!
                $session->invalidate();
                return $this->redirect($this->get('router')->generate('ukm_dip_login'));
    		}
    	}
    	else {
    		$session = new Session();
    		$session->start();
    	}

		// Generate token entity
		$token = new Token();
		// Update session with token
		$session->set('token', $token->getToken());
		// Update database with the new token
		$em = $this->getDoctrine()->getManager();
    	$em->persist($token);
    	$em->flush();
		
		// Send token to Delta
		$curl->post(array('location' => $location, 'token' => $token->getToken()));
		$res = $curl->process($this->dipURL);
		// Redirect to Delta
        $url = $this->deltaLoginURL.'?token='.$token->getToken().'&rdirurl='.$location;
        $url = $this->addScope($url);
        return $this->redirect($url);
    }

    /**
     * Legger til krav om scope til Delta.
     * Hvis scope følger innloggingsforespørselen, vil Delta først kreve at informasjonen vi ber om er lagt inn av brukeren,
     * og deretter sende den til oss på receive.
     *
     * @param $url - En fullverdig URL til delta-innlogginen.
     * @return $url - En URL inkl. scope.
     */
    public function addScope( $url ) {
        // Hvis vi vil be om mer informasjon fra Delta:
        if( $this->container->hasParameter('ukm_dip.scope') ) {
            if( strpos($url, '?') ) {
                $url = $url.'&scope=';
            }  
            else {
                $url = $url.'?scope=';
            }
            $url = $url . implode($this->container->getParameter('ukm_dip.scope'), ',');
        }  
        return $url;
    }

    public function receiveAction() {
		// Receives a JSON-object in a POST-request from Delta
		// This is all user-data, plus a token
    	$request = Request::CreateFromGlobals();

    	$data = json_decode($request->request->get('json'));
    	$repo = $this->getDoctrine()->getRepository('UKMDipBundle:Token');
    	$existingToken = $repo->findOneBy(array('token' => $data->token));
    	// Set token as authenticated
    	if (!$existingToken) throw new Exception('Token does not exist', 20005);
    	$existingToken->setAuth(true);
    	$existingToken->setUserId($data->delta_id);

    	$em = $this->getDoctrine()->getManager();
    	$em->persist($existingToken);
    	$em->flush();

    	// Find or update user
    	$userRepo = $this->getDoctrine()->getRepository('UKMDipBundle:User');
    	$user = $userRepo->findOneBy(array('deltaId' => $data->delta_id));
    	if (!$user) {
			// Hvis user ikke finnes
    		$user = new User();
    	}

        // Vi har ikke nødvendigvis mottatt all data, så her bør det sjekkes. Kan også lagre null.
    	$user->setDeltaId($data->delta_id);
        if($data->email)
            $user->setEmail($data->email);
        if($data->phone)
            $user->setPhone($data->phone);
        if($data->address)
            $user->setAddress($data->address);
        if($data->post_number)
            $user->setPostNumber($data->post_number);
		if($data->post_place)
            $user->setPostPlace($data->post_place);
		if($data->first_name)  
            $user->setFirstName($data->first_name);
		if($data->last_name)
            $user->setLastName($data->last_name);
        if($data->facebook_id)
		  $user->setFacebookId($data->facebook_id);
		if($data->facebook_id_unencrypted)
            $user->setFacebookIdUnencrypted($data->facebook_id_unencrypted);
		if($data->facebook_access_token)
            $user->setFacebookAccessToken($data->facebook_access_token);

		$time = new DateTime();
		$user->setBirthdate($time->getTimestamp());

		$em->persist($user);
		$em->flush();

    	return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Received'));
    }

}
