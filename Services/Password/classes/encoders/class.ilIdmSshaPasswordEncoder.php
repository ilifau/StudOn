<?php
// fau: idmPass - new class ilIdmSshaPasswordEncoder.

require_once 'Services/Password/classes/class.ilBasePasswordEncoder.php';

/**
 * Class ilIdmSshaPasswordEncoder
 * @package ServicesPassword
 */
class ilIdmSshaPasswordEncoder extends ilBasePasswordEncoder
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
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            require_once 'Services/Password/exceptions/class.ilPasswordException.php';
            throw new ilPasswordException('Invalid password.');
        }

        if (empty($salt)) {
            $salt = pack("CCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand());
        }

        return "{SSHA}" . base64_encode(sha1($raw . $salt, true) . $salt);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        // check encoding type
        $prefix = substr($encoded, 0, 6);
        if ($prefix != '{SSHA}') {
            return false;
        }

        // extract the salt
        if (empty($salt)) {
            $content = base64_decode(substr($encoded, 6));
            $salt = substr($content, 20);
        }

        return $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'idmssha';
    }
}
