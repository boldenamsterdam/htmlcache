<?php
/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * HTML Cache model settings
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 * @author Klearchos Douvantzis
 */

namespace bolden\htmlcache\models;


class Settings extends \craft\base\Model
{
    public $enableGeneral = 1;
    public $forceOn = 0;
    public $optimizeContent = 0;
    public $cacheDuration = 3600;
	public $purgeCache = 0;
	public $disablePreviewCache = 1;
    public $excludedUrlPaths = [];

    public function rules() {
        return [
            [ ['enableGeneral', 'forceOn', 'optimizeContent', 'purgeCache', 'disablePreviewCache' ], 'boolean' ],
            [ ['cacheDuration' ], 'integer' ],
        ];
    }
}
