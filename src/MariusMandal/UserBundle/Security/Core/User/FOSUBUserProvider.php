<?php
namespace MariusMandal\UserBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseClass;
use Symfony\Component\Security\Core\User\UserInterface;

class FOSUBUserProvider extends BaseClass
{

    /**
     * {@inheritDoc}
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $username = $response->getUsername();

        //on connect - get the access token and the user ID
        $service = $response->getResourceOwner()->getName();

        $setter = 'set'.ucfirst($service);
        $setter_id = $setter.'Id';
        $setter_token = $setter.'AccessToken';

        //we "disconnect" previously connected users
        if (null !== $previousUser = $this->userManager->findUserBy(array($property => $username))) {
            $previousUser->$setter_id(null);
            $previousUser->$setter_token(null);
            $this->userManager->updateUser($previousUser);
        }

        //we connect current user
        $user->$setter_id($username);
        $user->$setter_token($response->getAccessToken());

        $this->userManager->updateUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $serviceName 			= $response->getResourceOwner()->getName();

        $username = $response->getUsername();
        $user = $this->userManager->findUserByUsername( $serviceName .'_'. $username );
        
		$setService 			= 'set'.ucfirst( $serviceName );
		$setServiceId 			= $setService.'Id';
		$setServiceAccessToken 	= $setService.'AccessToken';
		$setServiceData			= '_'.$setService.'Data';

        //when the user is registrating
        if (null === $user) {
            $setter_id = $setService.'Id';
            $setter_token = $setService.'';

            // create new user here
            $user = $this->userManager->createUser();

            $user->$setServiceId( $username );
            $user->$setServiceAccessToken( $response->getAccessToken() );

            $user->setUsername($serviceName .'_'. $username);
			$user->setEnabled(true);

			$this->$setServiceData( $user, $response );

            $this->userManager->updateUser($user);
            return $user;
        }

        //if user exists - go with the HWIOAuth way
        $user = parent::loadUserByOAuthUserResponse($response);
        $user->$setServiceAccessToken( $response->getAccessToken() );

        return $user;
    }
        
    private function _setFacebookData( $user, $response ) {   	
		$user->setEmail( $response->getEmail() );
		$user->setPassword( $response->getUsername() );

		$data = $response->getResponse();
		
		// GET CORRECT USER ID (NOT API KEY ENCRYPTED )
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, str_replace('www.', 'graph.', $data['link'] ) );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $graphData = json_decode( curl_exec($ch) );
        curl_close($ch);
		
		$user->setFirstName( $data['first_name'] );
		$user->setLastName( $data['last_name'] );
		$user->setGender( $data['gender'] );
		#$user->setUsername( $data['username'] );
		$user->setFacebookIdUnencrypted( $graphData->id );
    }

}