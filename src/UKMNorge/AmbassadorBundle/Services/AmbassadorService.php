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
		$inv = new SQLins('ukm_ambassador_personal_invite');
		$inv->add('invite_phone', $phone);
		$inv->add('invite_code', 0);
		$inv->add('invite_confirmed', 'true');
		$inv->add('pl_id', "0");
		// echo $inv->debug();
		$inv->run();

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

}