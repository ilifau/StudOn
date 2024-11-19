<?php
/**
 * fau: customPatches - patches for testing functionalities
 */
class ilTestingPatches
{
    /** @var ilDBInterface  */
    protected $db;

    public function __construct() {
        global $DIC;
        $this->db = $DIC->database();
    }


    /**
     * fau: idmPass - test password encoder (new password will be saved for the account)
     */
    public function testIdmPass($params = array('login' => '', 'password'=> '', 'encoding' => 'idmssha'))
    {
        // needed for user object update
        if (!defined("ILIAS_HTTP_PATH")) {
            define("ILIAS_HTTP_PATH", "http://localhost");
        }

        $login = (string) $params['login'];
        $password = (string) $params['password'];
        $encoding = (string) $params['encoding'];

        $user_id = ilObjUser::_lookupId($login);
        if (empty($user_id)) {
            echo "User $login not found.\n";
            return;
        }
        $user_obj = new ilObjUser($user_id);

        $manager = ilUserPasswordManager::getInstance();
        $manager->setEncoderName($encoding);

        echo "Encode password '$password' for User '$login' with encoding '$encoding'. \n";
        $manager->encodePassword($user_obj, $password);

        echo "Verify password '$password' for User '$login' with encoding '$encoding'. \n";
        $valid = $manager->verifyPassword($user_obj, $password);

        echo $valid ? "valid\n" : "not valid\n";

        if ($valid) {
            echo "Store password '$password' for User '$login' with encoding '$encoding'. \n";
        }

        $user_obj->update();
    }
}

