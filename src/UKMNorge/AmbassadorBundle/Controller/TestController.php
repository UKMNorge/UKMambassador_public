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
		$ambassador = $ambassadorService->get();
		$mail = $ambassadorService->mail();
		
	}
}