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
     * @var integer
     *
     * @ORM\Column(name="phone", type="integer", nullable=true, nullable=true)
     */
    protected $phone;


    /**
     * @var int
     *
     * @ORM\Column(name="kommune_id", type="string", nullable=true)
     */
    protected $kommune_id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    protected $address;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_number", type="integer", nullable=true)
     */
    protected $postNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="post_place", type="string", length=255, nullable=true)
     */
    protected $postPlace;

    /**
     * @var integer
     *
     * @ORM\Column(name="birthdate", type="integer", nullable=true)
     */
    protected $birthdate = null;

     /**
     *
     * @ORM\Column(name="gender", type="string", length=10, nullable=true)
     *
     */
    protected $gender;


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

        $this->setPhone($data->phone);
        
        $this->setPostNumber($data->post_number);
        $this->setPostPlace($data->post_place);
        $this->setAddress($data->address);
        #$date = new DateTime();
        #$date->setTimestamp( $data->birthdate-> );
        #$this->setBirthdate($data->birthdate);
        //var_dump($data->birthdate);
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
        return $this->kommune_id;
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

    /**
     * Set phone
     *
     * @param integer $phone
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return integer 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return User
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set postNumber
     *
     * @param integer $postNumber
     * @return User
     */
    public function setPostNumber($postNumber)
    {
        $this->postNumber = $postNumber;

        return $this;
    }

    /**
     * Get postNumber
     *
     * @return integer 
     */
    public function getPostNumber()
    {
        return $this->postNumber;
    }

    /**
     * Set postPlace
     *
     * @param string $postPlace
     * @return User
     */
    public function setPostPlace($postPlace)
    {
        $this->postPlace = $postPlace;

        return $this;
    }

    /**
     * Get postPlace
     *
     * @return string 
     */
    public function getPostPlace()
    {
        return $this->postPlace;
    }

    /**
     * Set birthdate
     *
     * @param integer $birthdate
     * @return User
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return integer 
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return User
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }
}
