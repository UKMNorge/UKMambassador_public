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
	private $categoryBaseUrl= 'http://ukm.no/ambassador/kategori/';
	private $feedUrl		= 'http://ukm.no/ambassador/feed/';
	private $feedFile		= 'feed.xml';
	
	private $tempDir		= 'ambassador/wordpressCache/';
	private $postDataDir	= 'postData/';
	private $categoryDataDir= 'category/';
	private $lastBuildFile	= 'lastbuild.txt';

	private $feedXML		= false;
	private $feedRaw		= false;

	/**
	 * 
	 * Class constructor
	 * @param UserManagerInterface
	 * @param ContainerInterface
	 *
	*/
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
         
        $system_tmp = rtrim( sys_get_temp_dir(), '/' ) .'/';        
        $this->tempDir = $system_tmp . 'ambassador/wordpressCache/';
        
        // Re-define variables
       	$this->feedFile			= $this->tempDir . $this->feedFile;
       	$this->postDataDir		= $this->tempDir . $this->postDataDir;
       	$this->categoryDataDir	= $this->tempDir . $this->categoryDataDir;
       	$this->lastBuildFile	= $this->tempDir . $this->lastBuildFile;
    }
	
	/**
	 *
	 * Return cached object of param URL
	 * If not in cache, reload whole cache
	 *
	 * @param String URL
	 *
	 * @return WPOO_Post
	 */
	public function load( $url ) {
		if( !$this->_cacheExist( $url ) ) {
			$this->_wpDataLoad();
		}
		return $this->_wpFromCache( $url );
	}

	/**
	 * Get list of all posts in gicen category
	 *
	 * @param category URL
	 *
	 * @return Array of WPOO_Posts
	 */
	public function getCategory( $category ) {
		$list = [];
		
		if( !file_exists( $this->categoryDataDir . $category . '.list' ) ) {
			$this->addCategory( $category );
		}
		
		$handle = @fopen($this->categoryDataDir . $category . '.list', "r");
		if ($handle) {
		    while (( $post = fgets($handle, 4096)) !== false) {
		        $list[] = $this->load( $post );
		    }
		    fclose($handle);
		}
		return $list;
	}
	
	/** 
	 * Add a new category to list of categories to be cached (and cache)
	 *
	 * @return void
	 */
	public function addCategory( $category ) {
		// Reload whole cache if necesseary
		if( $this->_cacheIsOutdated() ) {
			$this->_wpDataLoad();
		}
		
		// Load and cache new category
		$this->_wpBuildCategory( $category );
	}
	
	/**
	 * Delete lastbuild-file to regenerate @ next load
	 *
	 * @return void
	 */
	public function deleteCache() {
		try {
			return @unlink( $this->lastBuildFile );
		} catch( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Return location of lastbuild-file
	 *
	 * @return string
	 */
	public function getLastbuildLocation() {
		return $this->lastBuildFile;
	}
	 
	
	/**
	 * Initiate load of feed, posts, pages and categories from RSS
	 * Write feed data to file
	 *
	 * @return void
	 */
	private function _wpDataLoad() {
		$this->_wpDataDependencies();
		
		ignore_user_abort(true);
		set_time_limit(60);

		$feed = $this->_getFeed();
		
		if( $this->_getFeedRaw() !== false ) {
			// WRITE FEED DATA
			$fh = fopen( $this->feedFile, 'w' );
			fwrite( $fh, $this->_getFeedRaw() );
			fclose( $fh );
			
			$this->_calcLastBuildDate();
		    $this->_wpDataBuild( );
		}
	}
	
	/**
	 * Load or return loaded XML-feed
	 *
	 * @return simpleXMLObject $feedXML
	 */
	private function _getFeed() {
		if( !$this->feedXML ) {
			require_once('UKM/curl.class.php');
			$curl = new UKMCURL();
			$curl->timeout(15);
			
			$this->feedRaw = $curl->request( $this->feedUrl );
			$this->feedXML = new SimpleXMLElement( $this->feedRaw );
		}
		return $this->feedXML;
	}
	
	/**
	 * Load or return loaded Feed raw-data (HTTP Response string)
	 *
	 * return String $feedRaw;
	 */
	private function _getFeedRaw() {
		if( !$this->feedRaw )
			$this->_getFeed();

		return $this->feedRaw;
	}
	
	/**
	 * Find lastBuildDate by file or feed data
	 *
	 * @return String $lastBuildDate
	 */
	private function _getLastBuildDate() {
		if( file_exists( $this->lastBuildFile ) ) {
			return file_get_contents( $this->lastBuildFile );
		} else {
			return $this->_calcLastBuildDate( );
		}
	}
	
	/** 
	 * Caclulate and write lastBuildDate from feed data
	 *
	 * @return String $lastBuildDate
	 */
	private function _calcLastBuildDate( ) {
		$feed = $this->_getFeed();
		
		$lastBuildDate 	= date('Ymd-His', strtotime( $feed->channel->lastBuildDate ) );
		
		$fh = fopen( $this->lastBuildFile, 'w');
		fwrite( $fh, $lastBuildDate );    
		fclose( $fh );
		
		return $lastBuildDate;
	}

	/**
	 * Actually load posts, pages and categories from WP to cache
	 *
	 * @return void
	 */
	private function _wpDataBuild( ) {
		$this->_wpClearPostDataCache();
		
		$feed = $this->_getFeed();
		// Loop and cache posts and pages as HTML
	    $posts = $feed->channel->item;
	    foreach( $posts as $post ) {
			# Calc cache name
			$file = $this->_wpCacheName( $post->link );
			# Load Object from UKM.no
			$content = $this->_loadWPOO_Post( $post->link );
			# Write object to file
			$fh = fopen(  $this->postDataDir . $file, 'w' );
			fwrite( $fh, $content );
			fclose( $fh );
	    }
		$this->_wpLoadCategories();	    
	}
	
	/**
	 * Delete all files in postData-directory
	 *
	 * @return void
	 */
	private function _wpClearPostDataCache() {
		// Remove old cache
		foreach (scandir( $this->postDataDir ) as $item) {
		    if ($item == '.' || $item == '..') continue;
		    unlink( $this->postDataDir .$item);
		}
	}

	/**
	 * Calculate cache filename of given link
	 *
	 * @return String $filename
	 */
	private function _wpCacheName( $link ) {
		return 'B'. $this->_getLastBuildDate() .'_'
				  . str_replace(array( str_replace('feed/','', $this->feedUrl), '/'), 
				  				array('','-'),
				  				rtrim( rtrim( $link ), '/') )
				  . '.html';
	}

	/**
	 * Load WPOO_Post-object from UKM.no by exportContent
	 * 
	 * @return JSON-String $curlresult
	 */
	private function _loadWPOO_Post( $link ) {
		require_once('UKM/curl.class.php');
		$curl = new UKMCURL();
		$curl->timeout(10);
		$curl->request( $link . '?exportContent=true' );
		
		return $curl->result;
	}
	
	/**
	 * Init reload of all category lists
	 *
	 * @return void
	 */
	private function _wpLoadCategories() {
	    // Reload all categories
   		foreach (scandir( $this->categoryDataDir ) as $item) {
		    if ($item == '.' || $item == '..') continue;
		    $this->_wpBuildCategory( str_replace('.list','', $item) );
		}
	}

	/**
	 * Load and cache given category list of posts
	 *
	 * return void
	 */
	private function _wpBuildCategory( $category ) {
		$this->_wpDataDependencies();
		
		require_once('UKM/curl.class.php');
		$curl = new UKMCURL();
		$curl->timeout(10);

		$xmlString = $curl->request( $this->categoryBaseUrl . $category .'/feed/' );
		$feed = new SimpleXMLElement( $xmlString );
		
		if( $xmlString !== false ) {
			$fh = fopen( $this->categoryDataDir . $category . '.list', 'w');
		    $posts = $feed->channel->item;
		    foreach( $posts as $post ) {
				fwrite( $fh, $post->link ."\r\n" );
			}
			fclose( $fh );
		}
	}
    
    /**
     * Is cache outdated?
     * True if:
     *  - file is deleted (by wordpress)
     *  - date in file and feed differs
     *
     * @return boolean $outdated;
     */
    private function _cacheIsOutdated() {
		if( !file_exists( $this->lastBuildFile ) ) {
			return true;
		}

		# APP DEVELOPMENT 		
		#return false;

		$feed 			= $this->_getFeed();	
	    $lastBuild		= file_get_contents( $this->lastBuildFile );
	    $lastBuildDate 	= date('Ymd-His', strtotime( $feed->channel->lastBuildDate ) );
	    
	    return $lastBuildDate != $lastBuild;
    }

	/**
	 * Is requested file cached?
	 *
	 * @return boolean cachefile_exists
	 */
    private function _cacheExist( $url ) {
    	if( $this->_cacheIsOutdated() )
    		return false;
    		
		$file = $this->_wpCacheName( $url );       	
		
		// SHOULD SOMEHOW CONFIRM REQUESTED FILE IS NOT GENERIC PATH (missing file + lastbuild @ wpCacheName?)	
	    return file_exists( $this->postDataDir.$file );
    }


	/**
	 * Load WPOO_Post from cache file
	 * 
	 * @param url ID
	 *
	 * @return WPOO_Post Object
	 */
    private function _wpFromCache( $url ) {
		$file = $this->_wpCacheName( $url );       	

       	if( !$this->_cacheExist( $url ) ) {
	       	$emptyClass = new stdClass();
	       	$emptyClass->title = '404 - Siden ikke funnet';
	       	$emptyClass->url = '';
	       	$emptyClass->pubDate = '';
	       	$emptyClass->content = 'Beklager, finner ikke siden!';
	       	return $emptyClass;
       	}
       	
       	$wpoo_post	= json_decode( file_get_contents( $this->postDataDir . $file ) );

       	$postDate 	= strtotime( $wpoo_post->raw->post_date );
       	$linkData = array( 	'year' 	=> date('Y', $postDate), 
       						'month' => date('m', $postDate), 
       						'date' 	=> date('d', $postDate), 
       						'id'	=> $wpoo_post->raw->post_name
       					  );

       	$wpoo_post->url = $this->container->get('router')->generate( 'wordpress_post', $linkData );

       	return $wpoo_post;
    }
    
        

	

	private function _wpDataDependencies() {
		if( !file_exists( dirname( $this->tempDir ) ) ) {
			try {
				var_dump( $this->tempDir );
				mkdir( $this->tempDir, 0777, true );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp-dir og XML-data. Kontakt UKM Norge!');
			}
		}

		if( !file_exists( $this->tempDir ) ) {
			try {
				mkdir( $this->tempDir, 0777, true );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp/ambassadør og XML-data. Kontakt UKM Norge!');
			}
		}

		if( !file_exists( $this->postDataDir ) ) {
			try {
				mkdir( $this->postDataDir, 0777, true );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp/ambassadør/post data. Kontakt UKM Norge!');
			}
		}

		if( !file_exists( $this->categoryDataDir ) ) {
			try {
				mkdir( $this->categoryDataDir, 0777, true );
			} catch ( Exception $e ) {
				throw new Exception('Kunne ikke aksessere temp/ambassadør/kategori data. Kontakt UKM Norge!');
			}
		}
	}

}