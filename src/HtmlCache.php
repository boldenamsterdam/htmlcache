<?php
/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * HTML Cache
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 */

namespace bolden\htmlcache;


use Craft;
use craft\base\Plugin;
use craft\web\Response;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Elements;
use craft\web\UrlManager;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use bolden\htmlcache\services\HtmlcacheService;
use bolden\htmlcache\assets\HtmlcacheAssets;
use bolden\htmlcache\models\Settings;

use yii\base\Event;

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
    protected function settingsHtml(): string
    {
        return \Craft::$app->getView()->renderTemplate(
            'html-cache/_settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }

    /**
     * Process the settings and check if the index needs to be altered
     *
     * @return function
     */
    public function setSettings(array $values)
    {
        HtmlcacheAssets::indexEnabled($values['enableIndex'] == 1 ? true : false);
        
        // Check if it actually worked
        if (stristr(file_get_contents($_SERVER['SCRIPT_FILENAME']), 'htmlcache') === false && $values['enableIndex'] == 1) {
            \Craft::$app->userSession->setError(Craft::t('The file ' . $_SERVER['SCRIPT_FILENAME'] . ' could not be edited'));
            return false;
        }

        if (!empty($values['purgeCache'])) {
            $this->setComponents(
                [
                    'htmlcacheService' => HtmlcacheService::class,
                ]
            );
            $this->htmlcacheService->clearCacheFiles();
        }
        return parent::setSettings($values);
    }

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * HtmlCache::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        self::$plugin = $this;
        
        $this->setComponents(
            [
                'htmlcacheService' => HtmlcacheService::class,
            ]
        );

        if ($this->isInstalled) {
            $this->htmlcacheService->checkForCacheFile();
            Event::on(Response::class, Response::EVENT_AFTER_SEND, function () {
                $this->htmlcacheService->createCacheFile();
            });

            Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function (Event $event) {
                $this->htmlcacheService->clearCacheFiles();
            });
        }
        
        // Do something after we're installed
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

        // Do something before we're uninstalled
        Event::on(
            Plugins::class,
            Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // reset index file if needed
                    HtmlcacheAssets::indexEnabled(false);
                }
            }
        );

        // Do something after we're uninstalled
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
        parent::init();
    }
    
    // Protected Methods
    // =========================================================================
    
}
            
            