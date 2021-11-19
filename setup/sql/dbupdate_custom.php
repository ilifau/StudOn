<?php declare(strict_types=1);?>
<#1>
<?php
/** @var ilDBInterface $ilDB */
if ($ilDB->tableExists('frm_settings') && !$ilDB->tableColumnExists('frm_settings', 'stylesheet')) {
    $ilDB->addTableColumn(
        'frm_settings',
        'stylesheet',
        [
            'type' => 'integer',
            'notnull' => true,
            'length' => 4,
            'default' => 0
        ]
    );
}
?>