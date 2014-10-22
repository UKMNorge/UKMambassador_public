<?php

namespace UKMNorge\AmbassadorBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use stdClass;
use Exception;
use SimpleXMLElement;
use UKMCURL;

class WordpressCacheService
{
    /**
     *
     * @var ContainerInterface 
     */
    protected $container;

	private $baseUrl		= 'http://ukm.no/ambassador/';
	private $feedUrl		= 'http://ukm.no/ambassador/feed/';
	private $feedFile		= '/tmp/ambassador/feed.xml';
	
	private $tempDir		= '/tmp/ambassador/';
	private $postDataDir	= '/tmp/ambassador/postData/';
	private $lastBuildFile	= '/tmp/ambassador/lastbuild.txt';

	/**
	 * 
	 * Class constructor
	 * @param UserManagerInterface
	 * @param ContainerInterface
	 *
	*/
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }
	
	
	public function load( $url ) {
		if( $this->_cacheExist( $url ) ) {
			return $this->_wordpressFromCache( $url );
		} else {
			$this->_wordpressDataLoad();
		}
		return $this->_wordpressFromCache( $url );
	}
	
    private function _wordpressFromCache( $url ) {
		list($file, $metafile) = $this->_wordpressFileNames( $url );       	

       	if( !$this->_cacheExist( $url ) ) {
	       	$emptyClass = new stdClass();
	       	$emptyClass->title = '';
	       	$emptyClass->link = '';
	       	$emptyClass->pubDate = '';
	       	$emptyClass->content = '';
	       	return $emptyClass;
       	}
       	
       	$page	 		= json_decode( file_get_contents( $this->postDataDir . $metafile ) );
       	$page->content	= file_get_contents( $this->postDataDir . $file );
       	
       	return $page;
    }
    
    private function _cacheExist( $url ) {
    	if( $this->_cacheIsOutdated() )
    		return false;
    		
		list($file, $metafile) = $this->_wordpressFileNames( $url );       	
		
		if( $file == false || $metafile == false )
			return false;
		
	    return file_exists( $this->postDataDir.$metafile ) && file_exists( $this->postDataDir.$file );
    }
    
    private function _cacheIsOutdated() {
		## DEVELOPMENT MODE
    	return false;
    	## DEVELOPMENT MODE
		require_once('UKM/curl.class.php');
		$UKMCURL = new UKMCURL();
		$UKMCURL->timeout(10);
		$xmlString = $UKMCURL->request( $this->feedUrl );
		$feed = new SimpleXMLElement( $xmlString );

    	// LOG LAST BUILD DATE
	    $lastBuildDate 	= date('Ymd-His', strtotime( $feed->channel->lastBuildDate ) );
		if( file_exists( $this->lastBuildFile ) ) {
		    $lastBuild		= file_get_contents( $this->lastBuildFile );
		    return $lastBuildDate != $lastBuild;
		}
		return $true;
    }
    
    private function _wordpressFileNames( $url ) {
       	$file 		= $this->_wordpressCacheName( $this->baseUrl. $url );
       	$metafile 	= str_replace('.html', '.meta.html', $file );

	   	return array($file, $metafile);
    }
    
	private function _wordpressDataLoad() {
		$this->_wordpressDataDependencies();
		
		ignore_user_abort(true);
		set_time_limit(60);

		require_once('UKM/curl.class.php');
		$UKMCURL = new UKMCURL();
		$UKMCURL->timeout(10);
		$xmlString = $UKMCURL->request( $this->feedUrl );
		
		if( $xmlString !== false ) {
			// WRITE FEED DATA
			$fh = fopen( $this->feedFile, 'w' );
			fwrite( $fh, $xmlString );
			fclose( $fh );

			$feed = new SimpleXMLElement( $xmlString );
			$lastBuildDate 	= date('Ymd-His', strtotime( $feed->channel->lastBuildDate ) );
					    
		    if( $this->_cacheIsOutdated() ) {
			    $this->_wordpressDataBuild( $lastBuildDate, $feed );
		    }
		}
	}
	
	private function _wordpressDataBuild( $lastBuildDate, $feed ) {
		// Write build data
		$fh = fopen( $this->lastBuildFile, 'w');
		fwrite( $fh, $lastBuildDate );    
		fclose( $fh );
		    
		// Remove old cache
		foreach (scandir( $this->postDataDir ) as $item) {
		    if ($item == '.' || $item == '..') continue;
		    unlink( $this->postDataDir .$item);
		}
		
	    $posts = $feed->channel->item;
		
		// Loop and cache posts and pages as HTML
	    foreach( $posts as $post ) {
			$file = $this->_wordpressCacheName( $post->link, $lastBuildDate );

			require_once('UKM/curl.class.php');
			$curl = new UKMCURL();
			$curl->timeout(10);
			$content = $curl->request( $post->link.'?exportContent=true' );
			
			// Write page content to cache file
			$fh = fopen(  $this->postDataDir . $file, 'w' );
			fwrite( $fh, $content );
			fclose( $fh );

			// Write page metadata to cache file
			$metadata = array('title' => (string) $post->title, 'link' => (string) $post->link, 'pubDate' => (string) $post->pubDate );
			$fh = fopen(  $this->postDataDir . str_replace('.html', '.meta.html', $file), 'w' );
			fwrite( $fh, json_encode( $metadata ) );
			fclose( $fh );
	    }
	}
	
	private function _wordpressCacheName( $link, $build=false ) {
		if( !file_exists( $this->lastBuildFile) )
			return false;

		if( !$build )
			$build = file_get_contents($this->lastBuildFile);

		return 'B'.$build .'_'
				  . str_replace(array( str_replace('feed/','', $this->feedUrl), '/'), array('','-'), rtrim( $link, '/') )
				  . '.html';
	}
	
	private function _wordpressDataDependencies() {
		if( !file_exists( dirname( $this->tempDir ) ) ) {
			try {
				mkdir( dirname( $this->tempDir ) );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp-dir og XML-data. Kontakt UKM Norge!');
			}
		}

		if( !file_exists( $this->tempDir ) ) {
			try {
				mkdir( $this->tempDir );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp/ambassadør og XML-data. Kontakt UKM Norge!');
			}
		}

		if( !file_exists( $this->postDataDir ) ) {
			try {
				mkdir( $this->postDataDir );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp/ambassadør/post data. Kontakt UKM Norge!');
			}
		}
	}

}