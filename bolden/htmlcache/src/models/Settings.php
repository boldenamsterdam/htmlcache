<?php
/**
 * Created by PhpStorm.
 * User: leevigraham
 * Date: 25/2/17
 * Time: 20:52
 */

namespace bolden\htmlcache\models;


class Settings extends \craft\base\Model
{
    public $enableGeneral = 1;
    public $enableIndex;
    public $forceOn;
    public $cacheDuration = 3600;

    public function rules() {
        return [
            [ ['enableGeneral', 'enableIndex', 'forceOn' ], 'boolean' ],
            [ ['cacheDuration' ], 'integer' ],
        ];
    }
}
