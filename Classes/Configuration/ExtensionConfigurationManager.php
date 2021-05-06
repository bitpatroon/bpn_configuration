<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 6-5-2021 21:37
 *
 *  All rights reserved
 *
 *  This script is part of a Bitpatroon project. The project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace BPN\Configuration\Configuration;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class \SPL\SplLibrary\Configuration\ExtensionConfigurationManager
 * This is the configuration manager which automatically manages the configuration instances. You can call
 * getConfiguration() to get the current configuration instance. If the instance does not yet exist it will be
 * automatically created.
 */
class ExtensionConfigurationManager implements SingletonInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $testInstanceConfiguration = false;

    /**
     * Sets instance configuration.
     * USE FOR TESTING ONLY.
     *
     * @param $configuration
     *
     * @return mixed
     *
     * @internal
     */
    public static function setInstanceConfiguration($configuration)
    {
        /** @var ExtensionConfigurationManager $extensionConfigurationManager */
        $extensionConfigurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ExtensionConfigurationManager::class);

        return $extensionConfigurationManager->testInstanceConfiguration = $configuration;
    }

    /**
     * intended use: for testing purposes.
     *
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
     */
    protected function getInstanceConfiguration()
    {
        $instanceConfiguration = [];
        $concreteConfigurationManager = ObjectAccess::getProperty(
            $this->configurationManager,
            'concreteConfigurationManager',
            true
        );
        $configuration = ObjectAccess::getProperty($concreteConfigurationManager, 'configuration', true);
        if (empty($configuration['extensionName']) && empty($configuration['pluginName'])) {
            throw new \RuntimeException(
                'Configuration class name could not be determined from this context', 1619695847
            );
        }

        $firstController = current($configuration["controllerConfiguration"]);
        $namespace = explode('\\', $firstController['className']);
        $vendor = current($namespace);

        $instanceConfiguration['extensionName'] = $configuration['extensionName'];
        $instanceConfiguration['pluginName'] = $configuration['pluginName'];
        $instanceConfiguration['vendorName'] = $configuration['vendorName'] ?? $vendor;
        $contentObject = $this->configurationManager->getContentObject();
        if (isset($contentObject)) {
            $instanceConfiguration['uid'] = $contentObject->data['uid'];
            $instanceConfiguration['table'] = ObjectAccess::getProperty($contentObject, 'table', true);
        }
        if (empty($instanceConfiguration['table'])) {
            $instanceConfiguration['table'] = 'anonymous';
        }
        if (empty($instanceConfiguration['uid'])) {
            $instanceConfiguration['uid'] = $GLOBALS['EXEC_TIME'];
        }

        return $instanceConfiguration;
    }

    /**
     * Gets unique instance key.
     *
     * @param array $configuration
     *
     * @return string
     */
    protected function getInstanceKey($configuration)
    {
        return sprintf(
            '%1$s_%2$s_%3$s_%4$s',
            $configuration['extensionName'],
            $configuration['pluginName'],
            $configuration['table'],
            $configuration['uid']
        );
    }

    /**
     * Gets configuration object name.
     *
     * @param array $configuration
     * @param bool  $global
     *
     * @return string
     */
    protected function getConfigurationClassName($configuration, $global = false)
    {
        $globalName = '';
        if ($global) {
            $globalName = 'Global';
        }
        $configurationClassName = sprintf(
            '%1$s\\%2$s\\Configuration\\%2$s%3$sConfiguration',
            $configuration['vendorName'],
            $configuration['extensionName'],
            $globalName
        );

        return $configurationClassName;
    }

    /**
     * @param $configuration
     * @param $global
     *
     * @return object|AbstractConfigurationContainer
     */
    public function createInstance($configuration, $global)
    {
        $objectName = $this->getConfigurationClassName($configuration, $global);

        return $this->getObjectManager()->get($objectName);
    }

    /**
     * Creates instance of configuration class for currently active extension.
     */
    public static function getConfigurationStatic(bool $global = false) : AbstractConfigurationContainer
    {
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)->get(__CLASS__);

        return $configurationManager->getConfigurationObject($global);
    }

    /**
     * Creates instance of configuration class for currently active extension.
     */
    public function getConfigurationObject(bool $global = false) : AbstractConfigurationContainer
    {
        $configuration = $this->getInstanceConfiguration();
        if ($global) {
            return $this->createInstance($configuration, $global);
        }
        $instanceKey = $this->getInstanceKey($configuration);
        if (!isset($this->instances[$instanceKey])) {
            $instance = $this->createInstance($configuration, false);
            $this->instances[$instanceKey] = $instance;
        }

        return $this->instances[$instanceKey];
    }

    /**
     * Gets specific configuration instance.
     *
     * @return AbstractConfigurationContainer
     */
    public function getSpecificConfigurationObject(
        string $vendorName,
        string $extensionName,
        string $pluginName,
        string $table = '',
        int $uid = 0
    ) {
        $instanceConfiguration['extensionName'] = $extensionName;
        $instanceConfiguration['vendorName'] = $vendorName;
        $instanceConfiguration['pluginName'] = $pluginName;
        $instanceConfiguration['uid'] = $uid;
        $instanceConfiguration['table'] = $table;
        if (empty($table)) {
            $instanceConfiguration['table'] = 'anonymous';
        }
        if (empty($uid)) {
            // will cache this configuration during the current run
            $instanceConfiguration['uid'] = $GLOBALS['EXEC_TIME'];
        }
        $instanceKey = $this->getInstanceKey($instanceConfiguration);
        if (!isset($this->instances[$instanceKey])) {
            $instance = $this->createInstance($instanceConfiguration, false);
            $this->instances[$instanceKey] = $instance;
        }

        return $this->instances[$instanceKey];
    }

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    protected function getObjectManager() : ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
