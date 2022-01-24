<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\FileUpload;

/**
 * Class ilObjectCustomIconImpl
 * TODO: Inject database persistence in future instead of using \ilContainer
 */
class ilObjectCustomIconImpl implements \ilObjectCustomIcon
{
    const ICON_BASENAME = 'icon_custom';

    /** @var Filesystem */
    protected $webDirectory;

    /** @var FileUpload */
    protected $upload;

    /** @var \ilCustomIconObjectConfiguration */
    protected $config;

    /** @var int */
    protected $objId;
    
    // fau: legacyIcons - class variables
    /** @var array|null */
    protected $settings;
    
    /** @var array  */
    protected $sizes = ['custom', 'big', 'small', 'tiny'];
    
    /** @var array  */
    protected $extensions = ['svg', 'png', 'gif', 'jpg'];
    // fau.
    
    /**
     * ilObjectCustomIconImpl constructor.
     * @param Filesystem                      $webDirectory
     * @param FileUpload                      $uploadService
     * @param ilCustomIconObjectConfiguration $config
     * @param                                 $objId
     */
    public function __construct(Filesystem $webDirectory, FileUpload $uploadService, \ilCustomIconObjectConfiguration $config, int $objId)
    {
        $this->objId = $objId;

        $this->webDirectory = $webDirectory;
        $this->upload = $uploadService;
        $this->config = $config;
    }

    /**
     * @return int
     */
    protected function getObjId() : int
    {
        return $this->objId;
    }

    /**
     * @inheritdoc
     */
    public function copy(int $targetObjId)
    {
        if (!$this->exists()) {
            \ilContainer::_writeContainerSetting($targetObjId, 'icon_custom', 0);
            return;
        }

        try {
            // fau: legacyIcons - copy icons when container is copied
            foreach ($this->sizes as $size) {
                foreach ($this->extensions as $ext) {
                    $filePath = $this->getRelativePath($this->getIconFileName($size, $ext));
                    if ($this->webDirectory->has($filePath)) {
                        $this->webDirectory->copy(
                            $filePath,
                            preg_replace(
                                '/(' . $this->config->getSubDirectoryPrefix() . ')(\d*)\/(.*)$/',
                                '${1}' . $targetObjId . '/${3}',
                                $filePath
                            )
                        );
                        \ilContainer::_writeContainerSetting($targetObjId, 'icon_' . $size, 1);
                    }
                }
            }
            // fau.
        } catch (\Exception $e) {
            \ilContainer::_writeContainerSetting($targetObjId, 'icon_custom', 0);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        if ($this->webDirectory->hasDir($this->getIconDirectory())) {
            try {
                $this->webDirectory->deleteDir($this->getIconDirectory());
            } catch (\Exception $e) {
            }
        }
        // fau: legacyIcons - delete the container settings for all sizes
        foreach ($this->sizes as $size) {
            \ilContainer::_deleteContainerSettings($this->getObjId(), 'icon_' . $size);
        }
        // fau.
    }

    /**
     * @return string[]
     */
    public function getSupportedFileExtensions() : array
    {
        return $this->config->getSupportedFileExtensions();
    }

    /**
     * @inheritdoc
     */
    public function saveFromSourceFile(string $sourceFilePath)
    {
        $this->createCustomIconDirectory();

        $fileName = $this->getRelativePath();

        if ($this->webDirectory->has($fileName)) {
            $this->webDirectory->delete($fileName);
        }

        $this->webDirectory->copy($sourceFilePath, $fileName);

        $this->persistIconState($fileName);
    }

    /**
     * @inheritdoc
     */
    public function saveFromHttpRequest()
    {
        $this->createCustomIconDirectory();

        $fileName = $this->getRelativePath();

        if ($this->webDirectory->has($fileName)) {
            $this->webDirectory->delete($fileName);
        }

        if ($this->upload->hasUploads() && !$this->upload->hasBeenProcessed()) {
            $this->upload->process();

            /** @var \ILIAS\FileUpload\DTO\UploadResult $result */
            $result = array_values($this->upload->getResults())[0];
            if ($result->getStatus() == \ILIAS\FileUpload\DTO\ProcessingStatus::OK) {
                $this->upload->moveOneFileTo(
                    $result,
                    $this->getIconDirectory(),
                    \ILIAS\FileUpload\Location::WEB,
                    $this->getIconFileName(),
                    true
                );
            }

            foreach ($this->config->getUploadPostProcessors() as $processor) {
                $processor->process($fileName);
            }
        }

        $this->persistIconState($fileName);
    }

    /**
     * @param string $fileName
     */
    protected function persistIconState(string $fileName)
    {
        if ($this->webDirectory->has($fileName)) {
            \ilContainer::_writeContainerSetting($this->getObjId(), 'icon_custom', 1);
        } else {
            \ilContainer::_writeContainerSetting($this->getObjId(), 'icon_custom', 0);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        // fau: legacyIcons - remove all icons and settings
        foreach ($this->sizes as $size) {
            foreach ($this->extensions as $ext) {
                $filePath = $this->getRelativePath($this->getIconFileName($size, $ext));
                if ($this->webDirectory->has($filePath)) {
                    $this->webDirectory->delete($filePath);
                }
            }
        }
        
        foreach ($this->sizes as $size) {
            \ilContainer::_writeContainerSetting($this->getObjId(), 'icon_' . $size, 0);
        }
        // fau.
    }

    /**
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    protected function createCustomIconDirectory()
    {
        $iconDirectory = $this->getIconDirectory();

        if (!$this->webDirectory->has(dirname($iconDirectory))) {
            $this->webDirectory->createDir(dirname($iconDirectory));
        }

        if (!$this->webDirectory->has($iconDirectory)) {
            $this->webDirectory->createDir($iconDirectory);
        }
    }

    /**
     * @return string
     */
    protected function getIconDirectory() : string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->config->getBaseDirectory(),
            $this->config->getSubDirectoryPrefix() . $this->getObjId()
        ]);
    }

    // fau: legacyIcons - get icon filename for old sizes and extensions
    /**
     * @param string $size
     * @param string $extension
     * @return string
     */
    protected function getIconFileName($size = '', $extension = '') : string
    {
        if ($size && $extension) {
            return 'icon_' . $size . '.' . $extension;
        }
    
        return self::ICON_BASENAME . '.' . $this->config->getTargetFileExtension();
    }
    // fau.

    // fau: legacyIcons - get the relative path for a specific filename
    /**
     * @param string $filename
     * @return string
     */
    protected function getRelativePath($filename = null) : string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->getIconDirectory(),
            isset($filename) ? $filename : $this->getIconFileName()
        ]);
    }
    // fau.

    // fau: legacyIcons - get all container settings at once
    /**
     * Get an icon setting
     * @param string $key
     * @return mixed
     */
    protected function getSetting($key)
    {
        if (!isset($this->settings)) {
            $this->settings = \ilContainer::_getContainerSettings($this->getObjId());
        }
        
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        } else {
            return 0;
        }
    }
    // fau.
    
    /**
     * @inheritdoc
     */
    public function exists() : bool
    {
        // fau: legacyIcons - check old sizes and extensions for existence
        foreach ($this->sizes as $size) {
            if ($this->getSetting('icon_' . $size)) {
                foreach ($this->extensions as $ext) {
                    if ($this->webDirectory->has($this->getRelativePath($this->getIconFileName($size, $ext)))) {
                        return true;
                    }
                }
            }
        }
        return false;
        // fau.
    }

    /**
     * @inheritdoc
     */
    public function getFullPath() : string
    {
        // fau: legacyIcons - fallback to old sizes and extensions for file path
        foreach ($this->sizes as $size) {
            if ($this->getSetting('icon_' . $size)) {
                foreach ($this->extensions as $ext) {
                    if ($this->webDirectory->has($this->getRelativePath($this->getIconFileName($size, $ext)))) {
                        return implode(DIRECTORY_SEPARATOR, [
                            \ilUtil::getWebspaceDir(),
                            $this->getRelativePath($this->getIconFileName($size, $ext))
                        ]);
                    }
                }
            }
        }
        // fau.
        // TODO: Currently there is no option to get the relative base directory of a filesystem
        return implode(DIRECTORY_SEPARATOR, [
            \ilUtil::getWebspaceDir(),
            $this->getRelativePath()
        ]);
    }

    /**
     * @param $source_dir
     * @param $filename
     * @throws \ILIAS\Filesystem\Exception\DirectoryNotFoundException
     * @throws \ILIAS\Filesystem\Exception\FileNotFoundException
     * @throws \ILIAS\Filesystem\Exception\IOException
     */
    public function createFromImportDir($source_dir)
    {
        $target_dir = implode(DIRECTORY_SEPARATOR, [
            \ilUtil::getWebspaceDir(),
            $this->getIconDirectory()
        ]);
        ilUtil::rCopy($source_dir, $target_dir);
        $this->persistIconState($this->getRelativePath());
    }
}
