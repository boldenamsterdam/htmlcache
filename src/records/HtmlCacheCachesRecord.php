<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace bolden\htmlcache\records;

use craft\db\ActiveRecord;

/**
 * Element record class.
 *
 * @property int $id ID
 * @property int $siteId
 * @property string $uri
 * @author bolden
 */
class HtmlCacheCache extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%htmlcache_caches}}';
    }
}