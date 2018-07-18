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
    public $cacheDuration = 3600;
    public $purgeCache = 0;

    public function rules() {
        return [
            [ ['enableGeneral', 'forceOn', 'purgeCache' ], 'boolean' ],
            [ ['cacheDuration' ], 'integer' ],
        ];
    }
}
