<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;
use SQL;
use SQLins;

class FortsettController extends Controller
{	
	/**
	 * Rendrer skjemaet.
	 */
	public function fortsettAction( Request $request ) {

	}

	/**
	 * Tar i mot nummer, finner ambassadør i tabellen og aktiverer deleted.
	 *
	 */
	public function nummerAction( Request $request, $nummer ) {
		require_once('UKM/SQL.class.php');
		#$nummer = $request->request->get('nummer');

		$qry = new SQLins("ukm_ambassador", array('amb_phone' => $nummer));
		$qry->add('deleted', 'false'); 
		$res = $qry->run();

		$qry = new SQL("SELECT * FROM `ukm_ambassador` WHERE `amb_phone` = '#nummer'", array('nummer' => $nummer));
		$res = $qry->run('array');

		$wordpressTheme = $this->get('ukm_amb.wordpressTheme');
    	$view_data = $wordpressTheme->prepareThemeData();

		$view_data['nummer'] = $res['amb_phone'];
		$view_data['fornavn'] = $res['amb_firstname'];
		return $this->render('UKMAmbBundle:Fortsett:success.html.twig', $view_data);
	}
}