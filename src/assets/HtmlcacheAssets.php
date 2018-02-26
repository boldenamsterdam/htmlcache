<?php
/**
 * HTML Cache plugin for Craft CMS 3.x
 *
 * html cahce
 *
 * @link      http://www.bolden.nl
 * @copyright Copyright (c) 2018 Bolden B.V.
 */

namespace bolden\htmlcache\assets;

use Craft;
/**
 * HtmlCache Service
 *
 * All of your plugin�s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Bolden B.V.
 * @package   HtmlCache
 * @since     0.0.1
 */
class HtmlcacheAssets
{
    public static function filename($withDirectory = true)
    {
        $protocol = 'http://';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        }

        $host = $_SERVER['HTTP_HOST'];
        if (empty($host) && !empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        }

        $uri = $_SERVER['REQUEST_URI'];
        $fileName = md5($protocol . $host . $uri) . '.html';
        if ($withDirectory) {
            $fileName = self::directory() . $fileName;
        }
        return $fileName;
    }

    public static function directory()
    {
        if (defined('CRAFT_STORAGE_PATH')) {
            return CRAFT_STORAGE_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR;
        }

        // Fallback to default directory
        return CRAFT_BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR;
    }

    public static function indexEnabled($enabled = true)
    {
        $replaceWith = "/*HTMLCache Begin*/\nrequire_once CRAFT_VENDOR_PATH . DIRECTORY_SEPARATOR . 'bolden' . DIRECTORY_SEPARATOR . 'htmlcache' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'HtmlcacheAssets.php';\nbolden\htmlcache\assets\HtmlcacheAssets::checkCache();\n/*HTMLCache End*/\n\n";
        $replaceFrom = "// Load Composer's autoloader";
        $file = $_SERVER['SCRIPT_FILENAME'];
        $contents = file_get_contents($file);
        if ($enabled) {
            if (stristr($contents, 'htmlcache') === false) {
                file_put_contents($file, str_replace($replaceFrom, $replaceWith . $replaceFrom, $contents));
            }
        } else {
            $beginning = "/*HTMLCache Begin*/";
            $end = "/*HTMLCache End*/\n\n";

            $beginningPos = strpos($contents, $beginning);
            $endPos = strpos($contents, $end);

            if ($beginningPos !== false && $endPos !== false) {
                $textToDelete = substr($contents, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
                file_put_contents($file, str_replace($textToDelete, '', $contents));
            }
        }
    }

    public static function checkCache($direct = true)
    {
        $file = self::filename(true);
        if (file_exists($file)) {
            if (file_exists($settingsFile = self::directory() . 'settings.json')) {
                $settings = json_decode(file_get_contents($settingsFile), true);
            } else {
                $settings = ['cacheDuration' => 3600];
            }
            if (time() - ($fmt = filemtime($file)) >= $settings['cacheDuration']) {
                unlink($file);
                return false;
            }
            $content = file_get_contents($file);

            // Check the content type
            $isJson = false;
            if (strlen($content) && ($content[0] == '[' || $content[0] == '{')) {
                // JSON?
                @json_decode($content);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $isJson = true;
                }
            }
            
            if ($isJson) {
                // Add extra JSON headers?
                if ($direct) {
                    header('Content-type:application/json');
                }
                echo $content;
            } else {
                if ($direct) {
                    header('Content-type:text/html;charset=UTF-8');
                }
                // Output the content
                echo $content;
            }

            // Exit the response if called directly
            if ($direct) {
                exit;
            }
        }
        return true;
    }
}
