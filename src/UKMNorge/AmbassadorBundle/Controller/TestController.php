<?php

namespace UKMNorge\AmbassadorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use stdClass;
use Exception;
use SQL;

class TestController extends Controller
{
	public function mailAction() {
		$ambassadorService = $this->get("ukm_amb.ambassador");
		$ambassador = $ambassadorService->get(572031635);
		#$mail = $ambassadorService->notifyContact($ambassador);

		throw new Exception('Test av mail-system.', 20050);
	}
}