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
use kontakt;
use UKMmail;

require_once('UKM/ambassador.class.php');
require_once('UKM/mail.class.php');
require_once('UKM/kontakt.class.php');

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
	
	public function create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday, $pl_id) {
		require_once('UKM/sql.class.php');
		// echo 'AmbassadorService: ';
		// Legg først til telefonnummeret i invitasjons-tabellen
		$inv = new SQLins('ukm_ambassador_personal_invite');
		$inv->add('invite_phone', $phone);
		$inv->add('invite_code', 0);
		$inv->add('invite_confirmed', 'true');
		$inv->add('pl_id', $pl_id);
		$inv->run();

		$ambassador = new ambassador( false );
		 
		// var_dump($ambassador);
		$ambassador = $ambassador->create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday);
		// Varsle lokalkontakten om at det har blitt opprettet en ny ambassadør
		$this->notifyContact($ambassador);
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
		$user = $this->container->get('dipb_user_provider')->loadUserByUsername($user);
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

	public function notifyContact($ambassador) {
	    // notifyContact
	    // Skrevet 16.02.16 av A. Hustad
	    // asgeirsh@ukmmedia.no
	    // Sender mail til lokalkontakten om at en ny ambassadør er opprettet.
	    if (!$ambassador) {
	            throw new Exception('Kunne ikke varsle lokalkontakten fordi oppretting av ambassadør feilet!', 20010);
	    }

	    $pl_id = $ambassador->getPlid();
	    $seasonService = $this->container->get('ukm_amb.season');
	    $season = $seasonService->getActive();

	    #$pl_id = 4383; // DevHack (Pl-id funker fint) 4383 = testkommune 3

	    $k_qry = new SQL("SELECT * FROM `smartukm_rel_pl_ab`
	                                            WHERE `pl_id` = '#pl_id';", array('pl_id' => $pl_id));

	    #echo $k_qry->debug();
	    $kommune = $k_qry->run();
	    $contacts = '';
	    while ($r = mysql_fetch_assoc($kommune)) {
	            $ab_id = $r['ab_id'];
	            $c = new kontakt($ab_id);
	            #var_dump($c);
	            $contacts .= $c->get('email'). ', ';
	    }
	    $contacts = rtrim($contacts, ', ');
	 
	    #var_dump($contacts);
	    if (empty($contacts)) {
	            $contacts = 'kontoer@ukm.no';
	    }
	    #$contacts = 'jardar@ukm.no';
	    $mail = new UKMmail();
	    $mail->subject('Ny ambassadør registrert!');
	    $mail->to($contacts);
	    #$mail->to('asgeirsh@ukmmedia.no');
	    $mail->message('Det har blitt registrert en ny UKM-ambassadør i din kommune: '.$ambassador->getFirstName() . ' '. $ambassador->getLastName().'. Logg inn på http://ukm.no -> for arrangører hvis du vil se dine ambassadører.');
	    $res = $mail->ok();
	    if (true !== $res) {
	            error_log('AmbassadorService: Kunne ikke sende mail til lokalkontakten. PHPMAILER-error');
	    }
	}


}