# HTML Cache plugin for Craft CMS 3.x

HTML Cache

## Requirements

This plugin requires Craft CMS 3 or later.

## Installation

To install the plugin, follow these instructions.

 
1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Add the repository in your composer.json file (make sure your ssh key is setup in bitbucket)

    "repositories": [
      {
        "type": "vcs",
        "url": "git@bitbucket.org:bolden/htmlcache-plugin.git"
      }
    ]
  
3. Then tell Composer to load the plugin:

        composer require bolden/htmlcache

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for HTML Cache.

## HTML Cache Overview

Creates a HTML Cached page for any non-cp GET request for the duration of one hour (configurable) or until an entry has been updated. 
To work in DEV-mode use the force option in the settings.


## Configuring HTML Cache

Use the plugin settings to configure it.

## Using HTML Cache

HTML Cache has a settings page where you can enable/disable both normal and ubercache. The ubercache alters the public/index.php file to include extra functionality before Craft gets initialised, eliminating the TTFB caused by Yii.

## HTML Cache Roadmap

Some things to do, and ideas for potential features:

* Release it

Brought to you by [Bolden B.V.](http://www.bolden.nl)
