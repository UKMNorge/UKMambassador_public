<?php

namespace UKMNorge\DipBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="UKMNorge\DipBundle\Entity\UserRepository")
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="delta_id", type="integer")
     */
    private $deltaId;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="phone", type="integer")
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_number", type="integer")
     */
    private $postNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="post_place", type="string", length=255)
     */
    private $postPlace;

    /**
     * @var integer
     *
     * @ORM\Column(name="birthdate", type="integer")
     */
    private $birthdate;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set deltaId
     *
     * @param integer $deltaId
     * @return User
     */
    public function setDeltaId($deltaId)
    {
        $this->deltaId = $deltaId;

        return $this;
    }

    /**
     * Get deltaId
     *
     * @return integer 
     */
    public function getDeltaId()
    {
        return $this->deltaId;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
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
}
