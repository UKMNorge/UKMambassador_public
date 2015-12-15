<?php

namespace UKMNorge\DipBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Token
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="UKMNorge\DipBundle\Entity\TokenRepository")
 */
class Token
{
    var $amb_string = 'AMB_STRING_TOKEN';

    public function __construct()
    {
        $this->token = $this->_generateToken();
        $this->auth = false;
        $this->userId = 0;
    }
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     */
    private $token;

    /**
     * @var boolean
     *
     * @ORM\Column(name="auth", type="boolean", options={"default" = false})
     */
    private $auth;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;


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
     * Set token
     *
     * @param string $token
     * @return Token
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set auth
     *
     * @param boolean $auth
     * @return Token
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;

        return $this;
    }

    /**
     * Get auth
     *
     * @return boolean 
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return Token
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    private function _generateToken() {
        $token = time() + '00000' + $this->amb_string;
        return hash('sha256', $token);
    }
}
