<?php

namespace UKMNorge\AmbassadorBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use stdClass;
use Exception;
use SimpleXMLElement;
use UKMCURL;
use ambassador;
use SQL;
use SQLins;

require_once('UKM/ambassador.class.php');

class AmbassadorService
{	
	public function __construct($container) {
		$this->container = $container;
	}

	public function get( $facebookID ) {
		$ambassador = new ambassador( $facebookID );
		
		if( !$ambassador->getId() ) {
			return false;
		}
		
		return $ambassador;
	}
	
	public function create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday) {
		require_once('UKM/sql.class.php');
		// echo 'AmbassadorService: ';
		// Legg først til telefonnummeret i invitasjons-tabellen
		// $inv = new SQLins('ukm_ambassador_personal_invite');
		// $inv->add('invite_phone', $phone);
		// $inv->add('invite_code', 0);
		// $inv->add('invite_confirmed', 'true');
		// $inv->add('pl_id', "0");
		// echo $inv->debug();
		// $inv->run();

		$ambassador = new ambassador( false );
		 
		// var_dump($ambassador);
		$ambassador = $ambassador->create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday);
		// var_dump($ambassador);
		return $ambassador;
	}
	
	public function setAddress( $faceID,  $address, $postalcode, $postalplace ) {
		$ambassador = $this->get( $faceID );
		$ambassador->setAddress( $address, $postalcode, $postalplace );
		return $ambassador;
	}

	public function setSize( $faceID, $size ) {
		$ambassador = $this->get( $faceID );
		$ambassador->setSize( $size );
		return $ambassador;
	}

	public function gotInvite( $phone ) {
		require_once('UKM/sql.class.php');
		
		$qry = new SQL("SELECT `invite_code` FROM `ukm_ambassador_personal_invite`
						WHERE `invite_phone` = '#phone'
						AND `invite_confirmed` = 'true';",
						array('phone'=>$phone));
		$res = $qry->run('field','invite_code');

		return $res;
	}

	public function gotPackage($facebookID) {
		require_once('UKM/sql.class.php');

		$ambassador = $this->get($facebookID);
		$user = $this->container->get('security.context')->getToken()->getUser();
		$qry = new SQLins('ukm_ambassador_skjorte', array('amb_id'=> $ambassador->getId() ) );
		$qry->add('sendt', 'true');

		$res = $qry->run();

		// Håndter om brukeren ikke finnes i skjorte-sendt-tabellen
		if ($res == 0) {
			$qry = new SQLins('ukm_ambassador_skjorte');
			
			$qry->add('amb_id', $ambassador->getId());
			$qry->add('size', 'unknown');
			$qry->add('adresse', $user->getAddress());
			$qry->add('postnr', $user->getPostNumber());
			$qry->add('poststed', $user->getPostPlace());

			$qry->add('sendt', 'true');

			$res = $qry->run();
		}
		return $res;
	}

	public function inviteStatus($phone) {
		require_once('UKM/sql.class.php');
		
		$qry = new SQL("SELECT `invite_confirmed` FROM `ukm_ambassador_personal_invite`
						WHERE `invite_phone` = '#phone';",
						array('phone'=>$phone));
		$res = $qry->run('field','invite_confirmed');

		return $res;
	}

}