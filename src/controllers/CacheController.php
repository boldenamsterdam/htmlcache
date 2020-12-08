<?php

namespace bolden\htmlcache\controllers;

use bolden\htmlcache\HtmlCache as Plugin;

use Craft;
use craft\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

/**
 * HtmlCache Controller
 *
 *
 * @author    Kurious Agency
 * @package   EmailEditor
 * @since     1.0.0
 */
class CacheController extends Controller
{
    public function actionClearCaches()
    {
        $this->requireLogin();
        $this->requirePostRequest();
        try {
            Plugin::$plugin->htmlcacheService->clearCacheFiles();
            Craft::$app->getSession()->setNotice(Craft::t('html-cache', 'Caches Cleared'));
        } catch (\Throwable $th) {
            Craft::$app->getSession()->setNotice(Craft::t('html-cache', 'Unable to Clear Caches'));
        }
        return;
    }

}
