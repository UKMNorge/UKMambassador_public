<?php

namespace UKMNorge\DipBundle\Controller;

// For å kunne dele sessions på flere sider
ini_set('session.cookie_domain', '.ukm.dev' );

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

use HttpRequest;
use UKMNorge\DipBundle\Entity\Token;
use UKMCurl;

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
    					return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Logged in successfully!'));
    				}
    				// Redirect til Delta-innlogging
    				$session->invalidate();
    				return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'Token not authorized'));
    			}
    			// Ingen token som matcher, ugyldig?
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
		echo $curl->process($dipURL);
    	
		// Redirect to Delta
    	$url = 'http://delta.ukm.dev/web/app_dev.php/login?token='.$token->getToken().'&rdirurl=ambassador';
    	return $this->redirect($url);
    	// var_dump($token);
    	// var_dump($session);

    	return $this->render('UKMDipBundle:Default:index.html.twig', array('name' => 'LoginTesting'));
    }

}
