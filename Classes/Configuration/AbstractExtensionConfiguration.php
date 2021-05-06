<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Sjoerd Zonneveld  <code@bitpatroon.nl>
 *  Date: 29-4-2021 10:32
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

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Abstract Configuration service.
 * Implement this for your own extension, implement the initializeApplication function so you have a
 * specific application setting container.
 */
abstract class AbstractExtensionConfiguration extends AbstractConfigurationContainer
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $pluginName = 'pi1';

    /**
     * @var string
     */
    protected $templateKey = '';

    /**
     * Initializes the configuration service with the right parameters for this application.
     */
    public function initializeObject()
    {
        $class = explode('\\', get_class($this));
        $class = array_pop($class);
        $extensionName = str_replace('Configuration', '', $class);
        $settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            $extensionName,
            $this->pluginName
        );

        $typoScriptAll = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            $extensionName,
            $this->pluginName
        );

        $pluginConfiguration = $this->templateKey ?: strtolower(
            sprintf("%s_%s", $extensionName, $this->pluginName)
        );
        $typoScript = [];
        if (isset($typoScriptAll['plugin.'][$pluginConfiguration . '.']['settings.'])) {
            $typoScript = $typoScriptAll['plugin.'][$pluginConfiguration . '.']['settings.'];

            if ($typoScript) {
                /** @var TypoScriptService $typoScriptService */
                $typoScriptService = GeneralUtility::makeInstance(ObjectManager::class)
                    ->get(TypoScriptService::class);

                $typoScript = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScript);
            }
        }

        $settings = array_merge_recursive($settings, $typoScript);
        $this->initializeApplication($settings);
    }

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
    }
}
