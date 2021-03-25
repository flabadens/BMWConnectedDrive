<?php


class Auth implements JsonSerializable
{
    /**
     * The auth token
     * @var string $token
     */
    protected $token = '';

    /**
     * The auth expires delay
     * @var int $expires
     */
    protected $expires = 0;

    public function __construct($token, $expires)
    {
        $this->token = $token;
        $this->expires = $expires;
    }

    /**
     * Get the auth token
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the auth token
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get the auth expires delay
     * @return int
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set the auth expires delay
     * @param $expires
     * @return $this
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * Used to be json encoded
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'token' => $this->getToken(),
            'expires' => $this->getExpires()
        ];
    }
}
