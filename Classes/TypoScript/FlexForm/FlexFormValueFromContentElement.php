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

namespace BPN\Configuration\TypoScript\FlexForm;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

class FlexFormValueFromContentElement extends AbstractPlugin
{

    /**
     * Gets the flex form from the current content object and gets the value using '.' separated path
     *
     * @param string $content
     * @param array  $configuration
     *
     * @return string
     */
    public function render(
        /** @noinspection PhpUnusedParameterInspection */
        $content,
        $configuration
    ) {
        $flexFormField = 'pi_flexform';
        $result = '';

        // field configuration
        if ($configuration['field']) {
            $flexFormField = $configuration['field'];
        }

        // read flex form data
        if (isset($this->cObj->data[$flexFormField])) {
            // read flex form data
            if (is_array($this->cObj->data[$flexFormField])) {
                // flex form data was already translated
                $flexFormData = $this->cObj->data[$flexFormField];
            } else {
                // create flex from data from string
                $flexFormData = GeneralUtility::xml2array($this->cObj->data[$flexFormField]);
            }

            // create array of flex form data
            $result = ArrayUtility::getValueByPath(
                $flexFormData,
                GeneralUtility::trimExplode('.', $configuration['flexFormFieldPath'])
            );
            if ($configuration['stdWrap']) {
                $this->cObj->stdWrap($result, $configuration['stdWrap']);
            }
        }

        return $result;
    }
}
