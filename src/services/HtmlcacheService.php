<?php

/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * HTML Cache Service
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 * @author Klearchos Douvantzis
 */

namespace bolden\htmlcache\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
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
 */
class HtmlcacheService extends Component
{
    private $uri;
    private $siteId;
    private $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uri = \Craft::$app->request->getParam('p', '');
        $this->siteId = \Craft::$app->getSites()->getCurrentSite()->id;
        $this->settings = HtmlCache::getInstance()->getSettings();
    }

    /**
     * Check if cache file exists
     *
     * @return void
     */
    public function checkForCacheFile()
    {
        // first check if we can create a file
        if (!$this->canCreateCacheFile()) {
            return;
        }
        $cacheEntry = HtmlCacheCache::findOne(['uri' => $this->uri, 'siteId' => $this->siteId]);

        // check if cache exists
        if ($cacheEntry) {
            $file = $this->getCacheFileName($cacheEntry->uid);
            if (file_exists($file)) {
                // load cache - may return false if cache has expired
                if ($this->loadCache($file)) {
                    return \Craft::$app->end();
                }
            }
        }
        // Turn output buffering on
        ob_start();
    }

    /**
     * Check if creation of file is allowed
     *
     * @return boolean
     */
    public function canCreateCacheFile()
    {
        // Skip if we're running in devMode and not in force mode
        if (\Craft::$app->config->general->devMode === true && $this->settings->forceOn == false) {
            return false;
        }

        // skip if not enabled
        if ($this->settings->enableGeneral == false) {
            return false;
        }

        // Skip if system is not on and not in force mode
        if (!\Craft::$app->getIsSystemOn() && $this->settings->forceOn == false) {
            return false;
        }

        // Skip if it's a CP Request
        if (\Craft::$app->getRequest()->getIsCpRequest()) {
            return false;
        }

        // Skip if it's an action Request
        if (\Craft::$app->getRequest()->getIsActionRequest()) {
            return false;
        }

        // Skip if it's a preview request
        if (\Craft::$app->getRequest()->getIsLivePreview()) {
            return false;
        }
        // Skip if it's a post request
        if (!\Craft::$app->getRequest()->getIsGet()) {
            return false;
        }
        // Skip if it's an ajax request
        if (\Craft::$app->getRequest()->getIsAjax()) {
            return false;
        }
        // Skip if route from element api
        if ($this->isElementApiRoute()) {
            return false;
        }
        // Skip if currently requested URL path is excluded
        if ($this->isPathExcluded()) {
            return false;
        }

        return true;
    }

    /**
     * Check if route is from element api
     *
     * @return boolean
     */
    private function isElementApiRoute()
    {
        $plugin = \Craft::$app->getPlugins()->getPlugin('element-api');
        if ($plugin) {
            $elementApiRoutes = $plugin->getSettings()->endpoints;
            $routes = array_keys($elementApiRoutes);
            foreach ($routes as $route) {
                // form the correct expression
                $route = preg_replace('~\<.*?:(.*?)\>~', '$1', $route);
                $found = preg_match('~' . $route . '~', $this->uri);
                if ($found) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if currently requested URL path has been added to list of excluded paths
     *
     * @return bool
     */
    private function isPathExcluded()
    {
        // determine currently requested URL path and the multi-site ID
        $requestedPath = \Craft::$app->request->getFullPath();
        $requestedSiteId = \Craft::$app->getSites()->getCurrentSite()->id;

        // compare with excluded paths and sites from the settings
        if (!empty($this->settings->excludedUrlPaths)) {
            foreach ($this->settings->excludedUrlPaths as $exclude) {
                $path = reset($exclude);
                $siteId = intval(next($exclude));

                // check if requested path is one of those of the settings
                if ($requestedPath == $path || preg_match('@' . $path . '@', $requestedPath)) {
                    // and if requested site either corresponds to the exclude setting or if it's unimportant at all
                    if ($requestedSiteId == $siteId || $siteId < 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Create the cache file
     *
     * @return void
     */
    public function createCacheFile()
    {
        // check if valid to create the file
        if ($this->canCreateCacheFile() && http_response_code() == 200) {
            $cacheEntry = HtmlCacheCache::findOne(['uri' => $this->uri, 'siteId' => $this->siteId]);
            // check if entry exists and start capturing content
            if ($cacheEntry) {
                $content = ob_get_contents();
                if($this->settings->optimizeContent){
                    $content = implode("\n", array_map('trim', explode("\n", $content)));
                }
                $file = $this->getCacheFileName($cacheEntry->uid);
                $fp = fopen($file, 'w+');
                if ($fp) {
                    fwrite($fp, $content);
                    fclose($fp);
                } else {
                    \Craft::info('HTML Cache could not write cache file "' . $file . '"');
                }
            } else {
                \Craft::info('HTML Cache could not find cache entry for siteId: "' . $this->siteId . '" and uri: "' . $this->uri . '"');
            }
        }
    }

    /**
     * clear cache for given elementId
     *
     * @param integer $elementId
     * @return boolean
     */
    public function clearCacheFile($elementId)
    {
        // get all possible caches
        $elements = HtmlCacheElement::findAll(['elementId' => $elementId]);
        // \craft::Dd($elements);
        $cacheIds = array_map(function ($el) {
            return $el->cacheId;
        }, $elements);

        // get all possible caches
        $caches = HtmlCacheCache::findAll(['id' => $cacheIds]);
        foreach ($caches as $cache) {
            $file = $this->getCacheFileName($cache->uid);
            if (file_exists($file)) {
                @unlink($file);
            }
        }


        // delete caches for related entry
        HtmlCacheCache::deleteAll(['id' => $cacheIds]);
        return true;
    }

    /**
     * Clear all caches
     *
     * @return void
     */
    public function clearCacheFiles()
    {
        FileHelper::clearDirectory($this->getDirectory());
        HtmlCacheCache::deleteAll();
    }

    /**
     * Get the filename path
     *
     * @param string $uid
     * @return string
     */
    private function getCacheFileName($uid)
    {
        return $this->getDirectory() . $uid . '.html';
    }

    /**
     * Get the directory path
     *
     * @return string
     */
    private function getDirectory()
    {
        // Fallback to default directory if no storage path defined
        if (defined('CRAFT_STORAGE_PATH')) {
            $basePath = CRAFT_STORAGE_PATH;
        } else {
            $basePath = CRAFT_BASE_PATH . DIRECTORY_SEPARATOR . 'storage';
        }

        return $basePath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR;
    }

    /**
     * Check cache and return it if exists
     *
     * @param string $file
     * @return mixed
     */
    private function loadCache($file)
    {
        if (file_exists($settingsFile = $this->getDirectory() . 'settings.json')) {
            $settings = json_decode(file_get_contents($settingsFile), true);
        } elseif (!empty($this->settings->cacheDuration)) {
            $settings = ['cacheDuration' => $this->settings->cacheDuration];
        } else {
            $settings = ['cacheDuration' => 3600];
        }
        if (time() - ($fmt = filemtime($file)) >= $settings['cacheDuration']) {
            unlink($file);
            return false;
        }
        \Craft::$app->response->data = file_get_contents($file);
        return true;
    }

}
