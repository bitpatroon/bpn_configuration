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

namespace BPN\Configuration\TypoScript;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypoScriptUtility
{
    /**
     * Gets value by path in legacy TypoScript (array('bla.' => array('hooba.' => array ('kek' => 1)))).
     *
     * @param array  $typoScript the TypoScript array
     * @param string $path       path with '.' separation 'bla.hooba.kek'
     *
     * @return mixed|null the result or NULL when nothing found
     */
    public static function getLegacyTypoScriptValueByPath($typoScript, $path)
    {
        if (empty($typoScript)) {
            return null;
        }

        $hasTrailingDot = '.' === $path[strlen($path) - 1];

        if ($hasTrailingDot) {
            $path = substr($path, 0, -1);
        }

        $keys = str_replace('.', '.:', $path);

        if ($hasTrailingDot) {
            $keys .= '.';
        }

        $keys = GeneralUtility::trimExplode(':', $keys);

        return ArrayUtility::getValueByPath($typoScript, $keys);
    }

    /**
     * Gets value by path in TypoScript (array('bla' => array('hooba' => array ('kek' => 1)))) or (array('bla' =>
     * array('hooba' => array ('kek' => array('_typoScriptNodeValue' => 1, array('and' => 'sdlfkjadf')))).
     *
     * @param array  $typoScript the TypoScript array
     * @param string $path       path with '.' separation 'bla.hooba.kek'.
     *
     * @return mixed the value from typoscript
     */
    public static function getTypoScriptValueByPath($typoScript, $path)
    {
        $keys = GeneralUtility::trimExplode('.', $path);

        try {
            return ArrayUtility::getValueByPath($typoScript, $keys);
        } catch (\Exception $e) {
            return '';
        }
    }
}
