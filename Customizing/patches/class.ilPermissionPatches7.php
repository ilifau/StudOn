<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fau: customPatches - permission patches for ILIAS 7
 */
class ilPermissionPatches7
{

    public function createMissingCopyOperations()
    {
        global $DIC;

        require_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

        $review = $DIC->rbac()->review();
        $pu = new ilPermissionUtils(true);

        $ops_id = $pu->getRbacOpsId('copy');
        foreach (['xcos', 'xlvo', 'xpdl', 'xsrl', 'xvid', 'xxco'] as $type) {
            $type_id = $review->getTypeId($type);
            \ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
        }
    }

    public function createMissingIvLpOperations()
    {
        global $DIC;

        require_once('./Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');

        $review = $DIC->rbac()->review();
        $pu = new ilPermissionUtils(true);

        $ops_id = $pu->getRbacOpsId('edit_learning_progress');
        foreach (['xvid'] as $type) {
            $type_id = $review->getTypeId($type);
            \ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
        }
    }


    public function initPluginsCopyPermissions()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermissions(['xcos', 'xlvo', 'xpdl', 'xsrl', 'xvid', 'xxco'], [
            ['write', 'copy']
        ]);

        $pu->copyPermissions(['xcos', 'xlvo', 'xpdl', 'xsrl', 'xvid', 'xxco'], [
            ['write', 'copy']
        ]);
    }


    public function initCourseRefLearningProgress() {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermissions(['crsr'], [
            ['write', 'read_learning_progress'],
            ['write', 'edit_learning_progress']
        ]);

        $pu->copyPermissions(['crsr'], [
            ['write', 'read_learning_progress'],
            ['write', 'edit_learning_progress']
        ]);
    }

    public function initInteractiveVideoLearningProgress() {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermissions(['xvid'], [
            ['write', 'edit_learning_progress']
        ]);

        $pu->copyPermissions(['xvid'], [
            ['write', 'edit_learning_progress']
        ]);
    }

    /**
     * 2024-01-22 (not yet executed)
     * Permissons for the LongEssayTask plugin were set in specific courses (law faculty)
     * Now the LongEssayAssessment should be used there instead
     * Creation of LongEssayTask is deactivated in the repository settings
     * 
     * Todo: when LongEssayAssessment is officially supported anywhere in studon
     * copy permissions from tst, set the 'maintain' permissions like the write permissions
     */
    public function initLongEssayAssessmentFromLongEssayTask() 
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('xlet','visible',			    'xlas','visible');
        $pu->copyDefaultPermission('xlet','read',				    'xlas','read');
        $pu->copyDefaultPermission('xlet','copy',				    'xlas','copy');
        $pu->copyDefaultPermission('xlet','write',				'xlas','write');
        $pu->copyDefaultPermission('xlet','delete',				'xlas','delete');
        $pu->copyDefaultPermission('xlet','maintain_task',		'xlas','maintain_task');
        $pu->copyDefaultPermission('xlet','maintain_writers',		'xlas','maintain_writers');
        $pu->copyDefaultPermission('xlet','maintain_correctors',	'xlas','maintain_correctors');
        $pu->copyDefaultPermission('xlet','edit_permission',		'xlas','edit_permission');

        $pu->copyDefaultPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_xlet', 'create_xlas'),
        ));

        $pu->copyPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_xlet', 'create_xlas'),
        ));
    }
}