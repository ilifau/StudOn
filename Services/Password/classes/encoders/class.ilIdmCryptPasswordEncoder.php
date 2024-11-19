<?php
// fau: idmPass - new class ilIdmCryptPasswordEncoder.

require_once 'Services/Password/classes/class.ilBasePasswordEncoder.php';

/**
 * Class ilIdmCryptPasswordEncoder
 * @package ServicesPassword
 */
class ilIdmCryptPasswordEncoder extends ilBasePasswordEncoder
{
    /**
     * @param array $config
     */
    public function __construct(array $config = array())
    {
    }

    /**
     * {@inheritdoc}
     * @throws ilPasswordException
     */
    public function encodePassword(string $raw, string $salt) : string
    {
        if ($this->isPasswordTooLong($raw)) {
            require_once 'Services/Password/exceptions/class.ilPasswordException.php';
            throw new ilPasswordException('Invalid password.');
        }

        return "{CRYPT}" . password_hash($raw, PASSWORD_BCRYPT);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid(string $encoded, string $raw, string $salt) : bool
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        // check encoding type
        $prefix = substr($encoded, 0, 7);
        if ($prefix != '{CRYPT}') {
            return false;
        }

        $encoded = substr($encoded, 7);

        return password_verify($raw, $encoded);
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return 'idmcrypt';
    }
}
