<?php

namespace UKMNorge\DipBundle\Controller;

// For å kunne dele sessions på flere sider
ini_set('session.cookie_domain', '.ukm.dev' );

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
    	require_once('UKM/curl.class.php');
    	// Dette er entry-funksjonen til DIP-innlogging.
    	// Her sjekker vi om brukeren har en session med en autentisert token, 
    	// og hvis ikke genererer vi en og sender brukeren videre til Delta.

    	// Send request to Delta with token-info
    	$dipURL = 'http://delta.ukm.dev/web/app_dev.php/dip/token';
    	$location = 'ambassador';

    	$curl = new UKMCurl();


    	// Har brukeren en session med token?
    	$session = $this->get('session');
    	if ($session->isStarted()) {
    		$token = $session->get('token');
    		if ($token) {
    			// Hvis token finnes, sjekk at det er autentisert i databasen
    			echo '<br>Token is: ';
    			var_dump($token);
    			echo '<br>';
    			$repo = $this->getDoctrine()->getRepository('UKMDipBundle:Token');
    			$existingToken = $repo->findOneBy(array('token' => $token));
    			var_dump($existingToken);
    			if ($existingToken) {
    				// Hvis token finnes
    				if ($existingToken->getAuth() == true) {
    					// Authorized, so trigger log in
    					$userId = $existingToken->getUserId();
    					var_dump($userId);
    					// Load user data?
    					$userProvider = $this->get('dipb_user_provider');
    					//$userProvider = $this->get('dipb_user_provider');
    					$user = $userProvider->loadUserByUsername($userId);
    		// 			var_dump($user);
						// die();
    					// Here, "public" is the name of the firewall in your security.yml
				        $token = new UsernamePasswordToken($user, $user->getPassword(), "secure_area", $user->getRoles());

				        // For older versions of Symfony, use security.context here
				        $this->get("security.context")->setToken($token);

				        // Fire the login event
				        // Logging the user in above the way we do it doesn't do this automatically
				        //now dispatch the login event
					    $request = $this->get("request");
				        $event = new InteractiveLoginEvent($request, $token);
				        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

				        // Redirect til en side bak firewall i stedet
				        return $this->redirect($this->generateUrl('ukm_amb_join_address'));
				        #return $this->redirectToRoute('ukm_amb_profile_homepage');
    					#return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Logged in successfully!'));
    				}
    				else {
    					// Hvis token ikke er autentisert enda
    					// Fjern lagret token
    					$session->invalidate();

    					return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Token not authorized'));
    					//TODO: Redirect til Delta-innlogging
    				}
    			}
    			// Token finnes, men ikke i databasen.
    			// Ingen token som matcher, ugyldig?
    			// Genererer ny og last inn siden på nytt?
    			return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Token does not exist'));
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
		$res = $curl->process($dipURL);
		var_dump($res); 
    	
		// Redirect to Delta
    	$url = 'http://delta.ukm.dev/web/app_dev.php/login?token='.$token->getToken().'&rdirurl=ambassador';
    	return $this->redirect($url);
    	// var_dump($token);
    	// var_dump($session);

    	return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'LoginTesting'));
    }

    public function receiveAction() {
		// Receives a JSON-object in a POST-request from Delta
		// This is all user-data, plus a token

    	$request = Request::CreateFromGlobals();

    	$data = json_decode($request->request->get('json'));
    	#$data = json_decode($request->query->get('json'));
    	#var_dump($data);
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

    	$user->setDeltaId($data->delta_id);
		$user->setEmail($data->email);
		$user->setPhone($data->phone);
		$user->setAddress($data->address);
		$user->setPostNumber($data->post_number);
		$user->setPostPlace($data->post_place);
		$user->setFirstName($data->first_name);
		$user->setLastName($data->last_name);
		$user->setFacebookId($data->facebook_id);
		$user->setFacebookIdUnencrypted($data->facebook_id_unencrypted);
		$user->setFacebookAccessToken($data->facebook_access_token);

		$time = new DateTime();
		$user->setBirthdate($time->getTimestamp());
		#$user->setBirthdate($data['birthdate']);
		// TODO: Set birthdate

		$em->persist($user);
		$em->flush();

    	return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Received'));
    }

}
