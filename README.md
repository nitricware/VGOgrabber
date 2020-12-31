# VGOgrabber

Downloads - if premium credentials are provided - up to 25 premium episodes from the VGO podcast and offers a new RSS feed.

## Prerequisites

First, you need a VGO premium subscription.

Second, you need a webspace with PHP 7.4 installed and some 100 MB of Storage.

Third you need to edit `settings.example.php`.
```php
/**
 * This must be the url where your downloaded episodes are stored.
 * It's the path to index.php with an appended podcasts/
 * i.e. https://yourserver.com/pathWhereIndexIs/podcasts
 */
$cache_url = "";
```

```php

/**
 * Your libsyn/VGO premium credentials.
 * Special characters must be manually escaped in this version.
 */
$email = "";
$password = "";
```

## Usage

Upload and create a cronjob that calls `index.php` periodically. Then point your Podcast app to `https://yourserver.com/pathWhereIndexIs/tmp/current_feed.xml`.

You may want to manage access to the files by using a `.htaccess`file. Overcast can handle feeds behind such a mechanism.