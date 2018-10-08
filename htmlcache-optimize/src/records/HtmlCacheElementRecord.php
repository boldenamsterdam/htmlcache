<?php
/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * HTML Cache Caches Element Record
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 * @author Klearchos Douvantzis
 */

namespace bolden\htmlcache\records;

use craft\db\ActiveRecord;

/**
 * Element record class.
 *
 * @property int $cacheId ID
 * @property int $elementId
 */
class HtmlCacheElement extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%htmlcache_elements}}';
    }
}
