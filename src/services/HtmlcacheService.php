<?php
/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * html cahce
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 */

namespace bolden\htmlcache\services;

use Craft;
use craft\base\Component;
use bolden\htmlcache\assets\HtmlcacheAssets;
use bolden\htmlcache\HtmlCache;
use craft\elements\Entry;
use craft\services\Elements;
use yii\base\Event;
use craft\elements\db\ElementQuery;
use bolden\htmlcache\records\HtmlCacheCache;
use bolden\htmlcache\records\HtmlCacheElement;

/**
 * HtmlCache Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Bolden B.V.
 * @package   HtmlCache
 * @since     0.0.1
 */
class HtmlcacheService extends Component
{
    public function checkForCacheFile()
    {
        if (!$this->canCreateCacheFile()) {
            return;
        }
        $uri = \Craft::$app->request->getParam('p');
        $siteId = \Craft::$app->getSites()->getCurrentSite()->id;
        $cacheEntry = HtmlCacheCache::findOne(['uri' => $uri, 'siteId' => $siteId]);
        if ($cacheEntry) {
            HtmlcacheAssets::checkCache($cacheEntry->uid, false);
            return \Craft::$app->end();
        }
        // Turn output buffering on
        ob_start();
    }
    
    public function canCreateCacheFile()
    {
        // Skip if we're running in devMode and not in force mode
        $settings = HtmlCache::getInstance()->getSettings();
        if (\Craft::$app->config->general->devMode === true && $settings->forceOn == false) {
            return false;
        }

        if ($settings->enableGeneral == false) {
            return false;
        }
        
        // Skip if system is not on and not in force mode
        if (!\Craft::$app->getIsSystemOn() && $settings->forceOn == false) {
            return false;
        }

        // Skip if it's a CP Request
        if (\Craft::$app->request->getIsCpRequest()) {
            return false;
        }

        // Skip if it's an action Request
        if (\Craft::$app->request->getIsActionRequest()) {
            return false;
        }

        // Skip if it's a preview request
        if (\Craft::$app->request->getIsLivePreview()) {
            return false;
        }
        // Skip if it's a post/ajax request
        if (!\Craft::$app->request->getIsGet()) {
            return false;
        }
        return true;
    }
    
    public function createCacheFile()
    {
        $uri = \Craft::$app->request->getParam('p');
        $siteId = \Craft::$app->getSites()->getCurrentSite()->id;
        if ($this->canCreateCacheFile() && http_response_code() == 200) {
            $cacheEntry = HtmlCacheCache::findOne(['uri' => $uri, 'siteId' => $siteId]);
            if ($cacheEntry) {
                $content = ob_get_contents();
                ob_end_clean();
                $file = $this->getCacheFileName($cacheEntry->uid);
                $fp = fopen($file, 'w+');
                if ($fp) {
                    fwrite($fp, $content);
                    fclose($fp);
                }
                else {
                    self::log('HTML Cache could not write cache file "' . $file . '"');
                }
                echo $content;
            } else {
                self::log('HTML Cache could not find cache entry for siteId: "' . $siteId . '" and uri: "' . $uri .'"');
            }
        }
    }
    
    public function clearCacheFile($elementId)
    {
        // get all possible caches
        $elements = HtmlCacheElement::findAll(['elementId' => $elementId]);
        // \craft::Dd($elements);
        $cacheIds = array_map(function($el) {
            return $el->cacheId;
        }, $elements);

        // get all possible caches
        $caches = HtmlCacheCache::findAll(['id' => $cacheIds]);
        foreach ($caches as $cache) {
            $file = $this->getCacheFileName($cache->uid);
            if (file_exists($file)) {
                unlink($file);
            }
        }


        // delete caches for related entry
        HtmlCacheCache::deleteAll(['id'=> $cacheIds]);
        return true;
    }

    public function clearCacheFiles()
    {
        // @todo split between all/single cache file
        foreach (glob($this->getCacheFileDirectory() . '*.html') as $file) {
            unlink($file);
        }
        HtmlCacheCache::deleteAll();
        return true;
    }
    
    private function getCacheFileName($uid, $withDirectory = true)
    {
        return HtmlcacheAssets::filename($uid, $withDirectory);
    }
    
    private function getCacheFileDirectory()
    {
        return HtmlcacheAssets::directory();
    }
    
    public function log($error, $level = '')
    {
        // Firstly, store in plugin log file (use $level to control log level)
        //HtmlcachePlugin::log(print_r($errors, true), $level, true);
    }
}
