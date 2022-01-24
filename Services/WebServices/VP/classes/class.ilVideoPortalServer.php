<?php
// fau: videoPortal - new class ilVideoPortalServer
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Video portal functions
 */
class ilVideoPortalServer extends Slim\App
{
    /** @var ilLanguage  */
    protected $lng;

    /** @var ilAccessHandler  */
    protected $access;

    /** @var string  */
    protected $token;

    /** @var ilExternalContentPlugin|null  */
    protected $externalContentPlugin;

    /**
     * @var array valid names for external content types (type => name => id field
     */
    protected $valid_xxco_data = [
        'clip' => ['rrze_video_clip' => 'ID'],
        'course' => ['rrze_video_course' => 'ID']
        ];



    /**
     * ilRestServer constructor.
     * @param array $container
     */
    public function __construct($container = [])
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('videoportal');
        $this->access = $DIC->access();

        parent::__construct($container);

        $this->token = ilCust::get('videoportal_token');
        $this->externalContentPlugin = $this->getExternalContentPlugin();
    }

    /**
     * Init server / add handlers
     */
    public function init()
    {
        $this->get('/check/{user}/{type}/{id}', array($this, 'checkAccess'));
        $this->get('/vp/check/{user}/{type}/{id}', array($this, 'checkAccess'));
        $this->get('/fix/vp/check/{user}/{type}/{id}', array($this, 'checkAccess'));
        $this->get('/dev/vp/check/{user}/{type}/{id}', array($this, 'checkAccess'));
        $this->get('/lab/vp/check/{user}/{type}/{id}', array($this, 'checkAccess'));
        $this->get('/test/vp/check/{user}/{type}/{id}', array($this, 'checkAccess'));
    }

    /**
     * Check Access to a video portal course or clip
     * @param Request  $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function checkAccess(Request $request, Response $response, array $args)
    {
        $user = $args['user'];
        $type = $args['type'];
        $id = $args['id'];

        // Authentication
        if (!empty($this->token)) {
            $authorization = $request->getHeaderLine('Authorization');
            if ($authorization != 'Bearer ' . $this->token) {
                return $this->getAccessResponse($response, false, 'vp_authorization_failed')
                    ->withStatus(\Slim\Http\StatusCode::HTTP_UNAUTHORIZED);
            }
        }

        if (!$this->isExternalContentPluginActive()) {
            return $this->getAccessResponse($response, false, 'vp_no_interface_active');
        }

        $user_id = ilObjUser::_findUserIdByAccount($user);
        if (empty($user_id)) {
            return $this->getAccessResponse($response, false, 'vp_no_user_found');
        }

        $ref_ids = $this->getExternalContentRefs($type, $id);
        if (empty($ref_ids)) {
            return $this->getAccessResponse($response, false, 'vp_no_reference_found');
        }

        foreach ($ref_ids as $ref_id) {
            if ($this->access->checkAccessOfUser($user_id, 'read', '', $ref_id )) {
                return $this->getAccessResponse($response, true, 'vp_access_granted');
            }
        }

        return $this->getAccessResponse($response, false, 'vp_no_access_granted');
    }

    /**
     * Get the response for an access check
     * @param Response $response
     * @param bool     $access
     * @param string   $messageId
     * @return Response
     */
    protected function getAccessResponse(Response  $response, $access, $messageId) {

        $json = [
            'access'=> (bool) $access,
            'message_en' => (string) $this->lng->txtlng('videoportal', $messageId, 'en'),
            'message_de' => (string) $this->lng->txtlng('videoportal', $messageId, 'de')
        ];

        return $response
            ->withStatus(\Slim\Http\StatusCode::HTTP_OK)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($json);
    }


    /**
     * Get the available external content objects for
     *
     * @param string $vp_type
     * @param string $vp_id
     * @return int[]  ref_ids
     */
    public function getExternalContentRefs($vp_type, $vp_id) {
        if (!$this->isExternalContentPluginActive()) {
            return [];
        }
        $this->externalContentPlugin->includeClass('class.ilExternalContentType.php');

        $return = [];
        foreach (ilExternalContentType::_getTypesData() as $data) {
            if (isset($this->valid_xxco_data[$vp_type][$data['type_name']]) && (
                $data['availability'] == ilExternalContentType::AVAILABILITY_CREATE ||
                $data['availability'] == ilExternalContentType::AVAILABILITY_EXISTING)
            ) {
                $field_name = $this->valid_xxco_data[$vp_type][$data['type_name']];
                $field_value = (string) $vp_id;
                $ref_ids = ilExternalContentType::_getRefIdsByTypeAndField($data['type_id'], $field_name, $field_value);
                $return = array_merge($return, $ref_ids);
            }
        }
        return $return;
    }

    /**
     * Get the external content plugin object
     */
    protected function getExternalContentPlugin() {
        return ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository','robj', 'ExternalContent');
    }

    /**
     * Check if external content plugin is active
     * @return bool
     */
    protected function isExternalContentPluginActive() {
        if (isset($this->externalContentPlugin)) {
            return $this->externalContentPlugin->isActive();
        }
        return false;
    }
}
