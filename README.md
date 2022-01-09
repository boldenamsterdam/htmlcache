# ⚠️ This plugin is no longer maintained.
# HTML Cache plugin for Craft CMS 3.x

Cache pages to HTML and boost website performance.

![img](https://www.bolden.nl/uploads/bolden-craft-html-cache.jpg)

This plugin generates static HTML files from your dynamic Craft CMS project. After a HTML file is generated, your webserver will serve that file instead of processing heavier and slower PHP scripts.

99% of your visitors will be served static HTML files. A cached file can be served thousands of times. That 1% could be a POST request (like AJAX forms) and btw:

* sections with a login won’t work
* the admin panel is also NOT cached


## Requirements

This plugin only requires Craft CMS 3 or later.


## HTML Cache Overview

Creates a HTML Cached page for any non-cp GET request for the duration of one hour (configurable) or until an entry has been updated. 
To work in DEV-mode: use the force option in the settings.


## Configuring HTML Cache

If the plugin is enabled it works out of the box and no special cache tags are needed. If DevMode in Craft CMS is enabled, you will have to force enable the plugin by enabling the 'Force On' plugin setting. You can also exclude url path(s) from being cached.


## Using HTML Cache

HTML Cache has a settings page where you can enable/disable it and flush the cache. 

If the plugin works correctly you will see the cached files in storage/runtime/htmlcache/ folder. To check the performance improvement please use the browser inspector. There you will be able to see that the loading times are improved.

## FAQ

**Q: Are all cache files deleted when updating an entry, or only the ones with a relation?**  
**A:** Only related cache files will be deleted after an update.
**Q: The installation fails and plugin does not work. **  
**A:** Make sure that the folder `storage/runtime/htmlcache` is created and there are read/write permissions.


## Credits

Made with ❤️ by Bolden – free to use and feedback is much appreciated!

Based – but rewritten, on the HTML Cache by CraftAPI in 2016
