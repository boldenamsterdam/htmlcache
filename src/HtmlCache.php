<?php

/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * HTML Cache Plugin
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 * @author Klearchos Douvantzis
 */

namespace bolden\htmlcache;


use Craft;
use craft\base\Plugin;
use craft\web\Response;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Elements;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use bolden\htmlcache\services\HtmlcacheService;
use bolden\htmlcache\models\Settings;
use bolden\htmlcache\utilities\CacheUtility;


use yii\base\Event;
use craft\elements\db\ElementQuery;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Asset;
use bolden\htmlcache\records\HtmlCacheCache;
use bolden\htmlcache\records\HtmlCacheElement;
use craft\elements\User;
use craft\elements\GlobalSet;
use craft\events\RegisterComponentTypesEvent;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Bolden B.V.
 * @package   HtmlCache
 * @since     0.0.1
 *
 */
class HtmlCache extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * HtmlCache::$plugin
     *
     * @var HtmlCache
     */
    public static $plugin;
    public $schemaVersion = '1.0.0';
    public $allowAnonymous = true;
    public $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * Returns whether the plugin should get its own tab in the CP header.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    public function hasSettings()
    {
        return true;
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     * @throws \Twig_Error_Loader
     * @throws \RuntimeException
     */
    protected function settingsHtml() : string
    {
        return \Craft::$app->getView()->renderTemplate(
            'html-cache/_settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }

    /**
     * Init plugin and initiate events
     */
    public function init()
    {
        self::$plugin = $this;

        // ignore console requests
        if ($this->isInstalled && !\Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->setComponents(
                [
                    'htmlcacheService' => HtmlcacheService::class,
                ]
            );
            // first check if there is a cache to serve
            $this->htmlcacheService->checkForCacheFile();

            // after request send try and create the cache file
            Event::on(Response::class, Response::EVENT_AFTER_SEND, function (Event $event) {
                $this->htmlcacheService->createCacheFile();
            });

            // on every update of an element clear the caches related to the element
            Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function (Event $event) {
                $this->htmlcacheService->clearCacheFile($event->element->id);
            });

            // on populated element put to relation table
            Event::on(ElementQuery::class, ElementQuery::EVENT_AFTER_POPULATE_ELEMENT, function ($event) {
                // procceed only if it should be created
                if ($this->htmlcacheService->canCreateCacheFile()) {
                    $elementClass = get_class($event->element);
                    if (!in_array($elementClass, [User::class, GlobalSet::class])) {
                        $uri = \Craft::$app->getRequest()->getPathInfo() ?: $event->element->uri;
                        $siteId = \Craft::$app->getSites()->getCurrentSite()->id;
                        $elementId = $event->element->id;

                        if (!$uri || strlen($uri) === 0) {
                            return;
                        }

                        // check if cache entry already exits otherwise create it
                        $cacheEntry = HtmlCacheCache::findOne(['uri' => $uri, 'siteId' => $siteId]);
                        if (!$cacheEntry) {
                            $cacheEntry = new HtmlCacheCache();
                            $cacheEntry->id = null;
                            $cacheEntry->uri = $uri;
                            $cacheEntry->siteId = $siteId;
                            $cacheEntry->save();
                        }
                        // check if relation element is already added or create it
                        $cacheElement = HtmlCacheElement::findOne(['elementId' => $elementId, 'cacheId' => $cacheEntry->id]);
                        if (!$cacheElement) {
                            $cacheElement = new HtmlCacheElement();
                            $cacheElement->elementId = $elementId;
                            $cacheElement->cacheId = $cacheEntry->id;
                            $cacheElement->save();
                        }
                    }
                }
            });

            // always reset purge cache value
            Event::on(Plugin::class, Plugin::EVENT_BEFORE_SAVE_SETTINGS, function ($event) {
                if ($event->sender === $this) {
                    $settings = $event->sender->getSettings();
                    if ($settings->purgeCache === '1') {
                        $this->htmlcacheService->clearCacheFiles();
                    }
                    // always reset value for purge cache
                    $event->sender->setSettings(['purgeCache' => '']);
                }
            });
        }
        
        // After install create the temp folder
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // create cache directory
                    $path = \Craft::$app->path->getStoragePath() . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR;
                    FileHelper::createDirectory($path);
                }
            }
        );

        // Before uninstall clear all cache
        Event::on(
            Plugins::class,
            Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // clear all files
                    $this->htmlcacheService->clearCacheFiles();
                }
            }
        );

        // After uninstall remove the cache dir
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // remove htmlcache dir
                    $path = \Craft::$app->path->getStoragePath() . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR;
                    FileHelper::removeDirectory($path);
                }
            }
        );

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(
                Utilities::class, 
                Utilities::EVENT_REGISTER_UTILITY_TYPES,
                function(RegisterComponentTypesEvent $event) {
                    $event->types[] = CacheUtility::class;
                }
        );
        }
        
        parent::init();
    }
    
    // Protected Methods
    // =========================================================================

}
            
            
