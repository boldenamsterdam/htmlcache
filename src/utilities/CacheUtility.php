<?php

namespace bolden\htmlcache\utilities;

use Craft;
use craft\base\Utility;
use bolden\htmlcache\HtmlCache;

class CacheUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('html-cache', 'HTML Cache');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'html-cache';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@vendor/bolden/htmlcache/src/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('html-cache/_utility');
    }
}

