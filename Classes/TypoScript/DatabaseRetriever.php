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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class user_GetFromDatabase utility (user-)functions to get stuff from the database when CONTENT is lacking
 * usage: getUidList
 *  configuration:
 *      table (string, required): the table from where data will be read
 *      where (array, optional): field => value
 *      orderBy (array, optional): field => asc|desc
 */
class DatabaseRetriever
{
    /**
     * @var array
     */
    protected static $resultCache = [];

    /**
     * @var ContentObjectRenderer
     */
    protected $contentObject;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Replaces ###markerName### in the where, with the output of the rendered marker.
     *
     * @param string $table
     * @param string $where
     * @param array  $configuration
     *
     * @return string
     */
    protected function replaceMarkers($table, $where, $configuration)
    {
        $result = $where;
        $markers = $this->contentObject->getQueryMarkers($table, $configuration);

        foreach ($markers as $marker => $markerValue) {
            $result = str_replace('###' . $marker . '###', $markerValue, $result);
        }

        return $result;
    }

    /**
     * Gets uid list from database (Userfunction).
     *
     * @param array $configuration
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getUidList(string $content, $configuration)
    {
        $hash = crc32(serialize($configuration));

        if (!isset(self::$resultCache['getUidList'][$hash])) {
            $content = $this->executeQueryByConfiguration($configuration);
            if (!$content) {
                return '';
            }
            self::$resultCache['getUidList'][$hash] = $content;
        }

        return self::$resultCache['getUidList'][$hash];
    }

    /**
     * Gets data from table.
     *
     * @param string $content
     * @param array  $configuration
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFromTable($content, $configuration)
    {
        $hash = crc32(serialize($configuration));

        if (!isset(self::$resultCache['getFromTable'][$hash])) {
            $content = $this->executeQueryByConfiguration($configuration, true);
            if (!$content) {
                return '';
            }
            self::$resultCache['getFromTable'][$hash] = $content;
        }

        return self::$resultCache['getFromTable'][$hash];
    }

    protected function executeQueryByConfiguration(array $configuration, bool $renderRefToTable = false) : string
    {
        $table = $configuration['table'];

        if (!$table) {
            return '';
        }

        /** Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);

        $renderObjName = $renderRefToTable ? '< ' . $table : '';
        $renderObjConf = [];
        $renderObjKey = '';

        if (isset($configuration['renderObj'])) {
            $renderObjName = $configuration['renderObj'];
            $renderObjKey = 'renderObj';
        }

        if (isset($configuration['renderObj.'])) {
            $renderObjConf = $configuration['renderObj.'];
        }

        $fields = ['uid'];
        if ($configuration['fields.'] && is_array($configuration['fields.'])) {
            $fields = $configuration['fields.'];
        }

        $where = [];
        if ($configuration['where.'] && is_array($configuration['where.'])) {
            $where = $configuration['where.'];
        }
        $orderBy = [];
        if ($configuration['orderBy.'] && is_array($configuration['orderBy.'])) {
            $orderBy = $configuration['orderBy.'];
        }

        $rows = $connection->select(
            $fields,
            $table,
            $where,
            [],
            $orderBy
        );

        if (!$rows) {
            return '';
        }

        if (!isset($configuration['renderObj'])) {
            return implode(',', array_keys($rows));
        }

        /** @var ContentObjectRenderer $parentContentObject */
        $parentContentObject = $GLOBALS['TSFE']->cObj;
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->setParent($parentContentObject->data, $parentContentObject->currentRecord);
        $parentContentObject->currentRecordNumber = 0;

        $content = '';
        foreach ($rows as $row) {
            ++$parentContentObject->currentRecordNumber;
            $contentObject->parentRecordNumber = $parentContentObject->currentRecordNumber;
            $GLOBALS['TSFE']->currentRecord = $table . ':' . $row['uid'];
            $parentContentObject->lastChanged($row['tstamp']);
            $contentObject->start($row, $table);
            $content .= $contentObject->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
        }

        return $content;
    }
}
