<?php

namespace MariusMandal\UserBundle\Controller;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use HWI\Bundle\OAuthBundle\Controller\ConnectController as HWIOauthController;

class ConnectController extends HWIOauthController
{
    public function connectServiceAction(Request $request, $service)
    {
    	try {
			$HWIOauthResult = parent::connectServiceAction( $request, $service );
		} catch( Exception $e ) {
			echo '<h1>WHOA! Exception</h1>' . $e->getMessage();
			die();
		}
		
		$content = $HWIOauthResult->getContent();
		
		if( strpos( $content, 'Successfully connected' ) !== false ) {
			return RedirectResponse::create( $this->container->get('router')->generate( 'ukm_amb_homepage' ) );
		}
		
		return $content;
	}
}