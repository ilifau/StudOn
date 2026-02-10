<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Data\ReferenceId;
use ILIAS\DI\Container;
use ILIAS\Export\ExportHandler\Consumer\ExportOption\BasicHandler as BasicExportOption;
use ILIAS\Export\ExportHandler\I\Consumer\Context\HandlerInterface as ilExportHandlerConsumerContextInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\CollectionInterface as ilExportHandlerConsumerFileIdentifierCollectionInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\HandlerInterface as ilExportHandlerConsumerFileIdentifierInterface;
use ILIAS\Export\ExportHandler\I\Info\File\CollectionInterface as ilExportHandlerFileInfoCollectionInterface;

class ilSkillExportOption extends BasicExportOption
{
    protected \ILIAS\Data\Factory $data_factory;

    public function init(Container $DIC): void
    {
        $this->data_factory = new \ILIAS\Data\Factory();
    }

    public function getExportType(): string
    {
        return 'xml';
    }

    public function getExportOptionId(): string
    {
        return 'il_skill_profile_exp';
    }

    public function getSupportedRepositoryObjectTypes(): array
    {
        return ['skee'];
    }

    public function getLabel(): string
    {
        # Return an empty label so that the export button is not displayed in the export tab.
        return '';
    }

    public function onExportOptionSelected(
        ilExportHandlerConsumerContextInterface $context
    ): void {
        # Do nothing, the export happens in another tab.
    }

    public function onDeleteFiles(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): void {
        foreach ($this->getFileSelection($context, $file_identifiers) as $file) {
            $exp_dir = $file->getDownloadInfo();
            $file_path = $file->getDownloadInfo() . DIRECTORY_SEPARATOR . $file->getFileName();
            if (is_file($file_path)) {
                unlink($file_path);
            }
        }
    }

    public function onDownloadFiles(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): void {
        foreach ($this->getFileSelection($context, $file_identifiers) as $file) {
            ilFileDelivery::deliverFileLegacy(
                $file->getDownloadInfo() . DIRECTORY_SEPARATOR . $file->getFileName(),
                $file->getFileName()
            );
        }
    }

    public function onDownloadWithLink(
        ReferenceId $reference_id,
        ilExportHandlerConsumerFileIdentifierInterface $file_identifier
    ): void {

    }

    public function getFiles(
        ilExportHandlerConsumerContextInterface $context
    ): ilExportHandlerFileInfoCollectionInterface {
        $collection_builder = $context->fileCollectionBuilder();
        $export_dir = ilExport::_getExportDirectory($context->exportObject()->getId(), "xml", 'skmg');
        $file_names = file_exists($export_dir) ? scandir($export_dir) : [];
        foreach ($file_names as $file_name) {
            if (in_array($file_name, ['.', '..'])) {
                continue;
            }
            $collection_builder = $collection_builder->withSPLFileInfo(
                new SplFileInfo($export_dir . DIRECTORY_SEPARATOR . $file_name),
                $this->data_factory->objId($context->exportObject()->getId()),
                $this
            );
        }
        return $collection_builder->collection();
    }

    public function getFileSelection(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): ilExportHandlerFileInfoCollectionInterface {
        $collection_builder = $context->fileCollectionBuilder();
        foreach ($this->getFiles($context) as $file_info) {
            if (!in_array($file_info->getFileIdentifier(), $file_identifiers->toStringArray())) {
                continue;
            }
            $collection_builder = $collection_builder->withFileInfo($file_info);
        }
        return $collection_builder->collection();
    }
}
