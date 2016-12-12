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


    /**
     * @var int
     *
     * @ORM\Column(name="kommune_id", type="string", nullable=true)
     */
    protected $kommune_id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function setData($data) {
        // Called from DIP on every login, collect what data you want here.
        $this->setFacebookId( $data->facebook_id );

        // Oppdater ambassadør-objekt med kommune-id også?
        $this->setKommuneId( $data->kommune_id ); 
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

    public function getKommuneId() {
        return $this->getKommuneId();
    }

    public function setKommuneId($kommune_id) {
        $this->kommune_id = $kommune_id;
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