<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: customPatches - Handles patch (cli) requests.
 *
 * @see ilCronStartUp
 */
class ilPatchStartUp
{
    private $client = '';
    private $username = '';
    private $password = '';

    /** @var ilAuthSession */
    private $authSession;

    /**
     * @param $a_client_id
     * @param $a_login
     * @param $a_password
     * @param ilAuthSession|null $authSession
     */
    public function __construct(
        $a_client_id,
        $a_login,
        $a_password,
        ilAuthSession $authSession = null
    ) {
        $this->client = $a_client_id;
        $this->username = $a_login;
        $this->password = $a_password;

        if (!in_array(php_sapi_name(), array_map('strtolower', $this->getValidPhpApis()))) {
           echo "The patch must be called from the cli!";
           exit(1);
        }

        include_once './Services/Context/classes/class.ilContext.php';
        ilContext::init(ilContext::CONTEXT_PATCH);

        // define client
        // @see mantis 20371
        $_GET['client_id'] = $this->client;

        require_once("Services/Init/classes/class.ilInitialisation.php");
        ilInitialisation::initILIAS();

        if (null === $authSession) {
            global $DIC;
            $authSession = $DIC['ilAuthSession'];
        }
        $this->authSession = $authSession;
    }


    /**
     * Login
     */
    public function login()
    {
        try {
            $this->authenticate();
        }
        catch(Exception $e) {
            $this->logout();
            echo $e->getMessage()."\n";
            exit(1);
        }
    }


    /**
     * Closes the current auth session
     */
    public function logout()
    {
        ilSession::setClosingContext(ilSession::SESSION_CLOSE_USER);
        $this->authSession->logout();
    }

    /**
     * Apply a patch given by its function name
     *
     * @param	string 	patch identifier (class.method)
     * @param	mixed	parameters (single oder array)
     */
    public function applyPatch($a_patch, $a_params = null)
    {
        $start = time();
        echo "Apply " . $a_patch . " ... \n";

        $class = substr($a_patch, 0, strpos($a_patch, '.'));
        $method = substr($a_patch, strpos($a_patch, '.') + 1);

        // get the patch class
        require_once ("./Customizing/patches/class.".$class.".php");
        $object = new $class;

        // call the patch method
        $error = $object->$method($a_params);

        // output the result and remember success
        if ($error != "") {
            echo $error . "\nFailed.";
        }
        else {
            echo "\nDone";
        }
        echo "\nTime (s): " .(time() - $start) . "\n\n";
    }

    /**
     * @return string[]
     */
    private function getValidPhpApis() : array
    {
        return [
            'cli'
        ];
    }

    /**
     * Start authentication
     * @return bool
     *
     * @throws ilCronException if authentication failed.
     */
    private function authenticate()
    {
        $credentials = new ilAuthFrontendCredentials();
        $credentials->setUsername($this->username);
        $credentials->setPassword($this->password);

        $provider_factory = new ilAuthProviderFactory();
        $providers = $provider_factory->getProviders($credentials);

        $status = ilAuthStatus::getInstance();

        $frontend_factory = new ilAuthFrontendFactory();
        $frontend_factory->setContext(ilAuthFrontendFactory::CONTEXT_CLI);

        $frontend = $frontend_factory->getFrontend(
            $this->authSession,
            $status,
            $credentials,
            $providers
        );

        $frontend->authenticate();

        switch ($status->getStatus()) {
            case ilAuthStatus::STATUS_AUTHENTICATED:
                ilLoggerFactory::getLogger('auth')->debug('Authentication successful; Redirecting to starting page.');
                return true;


            default:
            case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
                throw new ilException($status->getTranslatedReason());
        }
    }
}
