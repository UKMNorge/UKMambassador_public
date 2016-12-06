<?php
// src/AppBundle/Entity/User.php

namespace UKMNorge\AmbassadorBundle\Entity;

use UKMNorge\UKMDipBundle\Entity\UserClass as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="dip_user")
*/
class User extends BaseUser
{

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     */
    protected $facebook_id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function setData($data) {
        // Called from DIP on every login, collect what data you want here.
        $this->setFacebookId( $data->facebook_id );
    }

    /**
     * Set facebook_id
     *
     * @param string $facebookId
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebook_id = $facebookId;

        return $this;
    }

    /**
     * Get facebook_id
     *
     * @return string 
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }
}