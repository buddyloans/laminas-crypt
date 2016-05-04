<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Crypt\Password;

use Traversable;
use Zend\Math\Rand;
use Zend\Stdlib\ArrayUtils;

/**
 * Bcrypt algorithm using crypt() function of PHP
 */
class Bcrypt implements PasswordInterface
{
    const MIN_SALT_SIZE = 22;

    /**
     * @var string
     *
     * Changed from 14 to 10 to prevent possibile DOS attacks
     * due to the high computational time
     * @see http://timoh6.github.io/2013/11/26/Aggressive-password-stretching.html
     */
    protected $cost = '10';

    /**
     * @var string
     */
    protected $salt;

    /**
     * Constructor
     *
     * @param array|Traversable $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            if ($options instanceof Traversable) {
                $options = ArrayUtils::iteratorToArray($options);
            } elseif (!is_array($options)) {
                throw new Exception\InvalidArgumentException(
                    'The options parameter must be an array or a Traversable'
                );
            }
            foreach ($options as $key => $value) {
                switch (strtolower($key)) {
                    case 'salt':
                        $this->setSalt($value);
                        break;
                    case 'cost':
                        $this->setCost($value);
                        break;
                }
            }
        }
    }

    /**
     * Bcrypt
     *
     * @param  string $password
     * @param  boolean $cryptuse
     * @throws Exception\RuntimeException
     * @return string
     */
    public function create($password, $cryptuse = false)
    {
        if (empty($this->salt)) {
            $salt = Rand::getBytes(self::MIN_SALT_SIZE);
        } else {
            $salt = $this->salt;
        }

        $options = [ 'cost' => $this->cost ];
        if (PHP_VERSION_ID < 70000) { // salt is deprecated from PHP 7.0
            $options['salt'] = $salt;
        }
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Verify if a password is correct against a hash value
     *
     * @param  string $password
     * @param  string $hash
     * @return bool
     */
    public function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Set the cost parameter
     *
     * @param  int|string $cost
     * @throws Exception\InvalidArgumentException
     * @return Bcrypt
     */
    public function setCost($cost)
    {
        if (!empty($cost)) {
            $cost = (int) $cost;
            if ($cost < 4 || $cost > 31) {
                throw new Exception\InvalidArgumentException(
                    'The cost parameter of bcrypt must be in range 04-31'
                );
            }
            $this->cost = sprintf('%1$02d', $cost);
        }
        return $this;
    }

    /**
     * Get the cost parameter
     *
     * @return string
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set the salt value
     *
     * @param  string $salt
     * @throws Exception\InvalidArgumentException
     * @return Bcrypt
     */
    public function setSalt($salt)
    {
        if (PHP_VERSION_ID >= 70000) {
            trigger_error("The salt support is deprecated starting from PHP 7.0.0", E_USER_DEPRECATED);
        }
        if (mb_strlen($salt, '8bit') < self::MIN_SALT_SIZE) {
            throw new Exception\InvalidArgumentException(
                'The length of the salt must be at least ' . self::MIN_SALT_SIZE . ' bytes'
            );
        }
        $this->salt = $salt;
        return $this;
    }

    /**
     * Get the salt value
     *
     * @return string
     */
    public function getSalt()
    {
        if (PHP_VERSION_ID >= 70000) {
            trigger_error("The salt support is deprecated starting from PHP 7.0.0", E_USER_DEPRECATED);
        } 
        return $this->salt;
    }
}
