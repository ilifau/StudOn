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
}