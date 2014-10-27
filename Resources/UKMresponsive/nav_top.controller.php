<?php
$active = 'ungdom';#get_option( 'ukm_top_page' );
$NAV_TOP = array('ungdom','din_monstring','voksneogpresse','ukmtv','arrangorer');

if( !defined('UKM_HOSTNAME') ) {
	define('UKM_HOSTNAME', 'ukm.no');
}

$DATA['nav_top'][] = (object) array('url' 		=> '//'.UKM_HOSTNAME,
									'title' 	=> 'for ungdom',
									'full_title'=> 'UKM for ungdom',
								    'active'	=> $active == 'ungdom');
								    
$DATA['nav_top'][] = (object) array('url' 		=> '//'.UKM_HOSTNAME.'/din_monstring/',
									'title' 	=> 'der du bor',
									'full_title'=> 'UKM der du bor',
								    'active'	=> $active == 'din_monstring');
								    
$DATA['nav_top'][] = (object) array('url' 		=> '//om.'.UKM_HOSTNAME.'/',
									'title' 	=> 'for voksne og presse',
									'full_title'=> 'UKM for voksne og presse',
								    'active'	=> $active == 'voksneogpresse');

$DATA['nav_top'][] = (object) array('url' 		=> '//tv.'.UKM_HOSTNAME.'/',
									'title' 	=> 'TV',
									'full_title'=> 'UKM-TV',
								    'active'	=> $active == 'ukmtv');
								    
/*
$DATA['nav_top'][] = (object) array('url' 		=> '//'.$_SERVER['HTTP_HOST'].'/internasjonalt/',
									'title' 	=> 'internasjonalt',
								    'active'	=> $active == 'internasjonalt');

$DATA['nav_top'][] = (object) array('url' 		=> '//'.$_SERVER['HTTP_HOST'].'/ambassador/',
									'title' 	=> 'ambassadører',
								    'active'	=> $active == 'ambassadorer');
*/
								    
$DATA['nav_top_right'][] = (object) array('url' => '//'.UKM_HOSTNAME.'/wp-login.php',
									'title' 	=> 'for arrangører',
									'full_title'=> 'UKM for arrangører',
								    'active'	=> $active == 'arrangorer');