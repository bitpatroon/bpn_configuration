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

use BPN\Configuration\TypoScript\TypoScriptUtility;

/**
 * Abstract Configuration service.
 * Note: deliberately not made singleton
 */
abstract class AbstractConfigurationContainer
{

    /**
     * Initializes your application
     *
     * @param array $settings
     */
    abstract protected function initializeApplication($settings);

    /**
     * Gets value and checks if not empty. If empty will throw exception with given message and code
     *
     * @param array  $settings      settings array
     * @param string $property      name of property
     * @param string $message       the message for the exception when property is not set
     * @param int    $exceptionCode code for the exception when property is not set
     *
     * @return mixed
     */
    protected function getRequiredValueFromSettings(
        array $settings,
        string $property,
        string $message,
        int $exceptionCode
    ) {
        if (!$settings) {
            throw new \RuntimeException(
                'TS settings are not configured correctly for this extension',
                1619730932
            );
        }
        $value = null;
        if (strpos($property, '.') === false) {
            if (!isset($settings[$property]) || !$settings[$property]) {
                $message = str_replace('{property}', $property, $message);
                throw new \RuntimeException($message, $exceptionCode);
            }
            $value = $settings[$property];
        } else {
            $value = TypoScriptUtility::getTypoScriptValueByPath($settings, $property);
            if (!$value) {
                $message = str_replace('{property}', $property, $message);
                throw new \RuntimeException($message, $exceptionCode);
            }
        }

        return $value;
    }

    /**
     * Gets value from settings array
     *
     * @param array  $settings settings array
     * @param string $property name of property or path to property
     *
     * @return mixed
     */
    protected function getValueFromSettings($settings, $property)
    {
        $value = null;
        if (strpos($property, '.') === false) {
            if (isset($settings[$property])) {
                $value = $settings[$property];
            }
        } else {
            $value = TypoScriptUtility::getTypoScriptValueByPath($settings, $property);
        }

        if (strpos($value, '{$') == true){
            $value = '';
        }

        return $value;
    }
}
