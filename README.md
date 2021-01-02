# VGOgrabber

Downloads - if premium credentials are provided - up to 25 premium episodes from the VGO podcast and offers a new RSS feed.

## Usage

1. get VGO premium subscription
2. get webspace with ~1GB storage and PHP 7.4 installed
3. modify `settings.example.php` and save as `settings.php`:
```php
/**
 * $cache_url must be the url where your downloaded episodes
 * are stored. It's the path to index.php with an appended
 * podcasts/
 * i.e. https://yourserver.com/pathWhereIndexIs/podcasts
 * 
 * $fixed_feed_url must be the url where the feed create by
 * this software is stored. It's the path to index.php with
 * tmp/current_feed.xml appended.
 * i.e. https://yourserver.com/pathWhereIndexIs/tmp/current_feed.xml
 */
$cache_url = "";
$fixed_feed_url = "";
```

```php

/**
 * Your libsyn/VGO premium credentials.
 * Special characters must be manually escaped in this version.
 */
$email = "";
$password = "";
```

4. upload to your webspace
5. create cronjob that calls `index.php` periodically (i.e. `php index.php` or `curl https://yourserver.com/pathWhereIndexIs/index.php`)
6. point your Podcast app to:
   `https://yourserver.com/pathWhereIndexIs/tmp/current_feed.xml`.
7. manage access to the files by using a `.htaccess`file. Overcast can handle feeds behind such a mechanism with a neat UI. Apple Podcast should too by subscribing to:
   `https://htaccessUser:htaccessPassword@yourserver.com/pathWhereIndexIs/tmp/current_feed.xml`
   
## How it works
`index.php` - when triggered - logs in to libsyn with the credentials provided in `settings.php` and the fetches the first page of the feed site (25 items). It then goes on to download those episodes and create a new RSS feed for apps like Overcast that contains all downloaded episodes.
So it basically mirrors the complete VGO podcast feed on your personal webspace.

## Motivation

VGO hosts their episodes on libsyn servers. For content creators libsyn offers apps instead of premium rss feeds for premium content. The libsyn apps are a mess. This software allows listeners to get rid of those apps.

## Contribution

I‘d love to see this project evolve into a community effort. So don’t hesitate to file issues and make pull requests. Contribute and make this piece of software worthy of the VGO seal of quality!

Feel free to message me on the VGO Discord. I'm `vcr80`.

## Changelog

* 1.0 - initial release
* 1.0.1 - fixed an issue that prevented Apple Podcasts to fetch the feed of VGOGrabber and made Apple Podcasts use the default VGO URL resulting in only showing non-premium content. Apps like Overcast did not have that problem.

## Known Issues and Limitations

- The code of `index.php` is ugly and should be split up into functions of `VGOGrabber` class.
- No real error management
- Game Store Guy and Community Show are not in the feed. I'm in contact with Michelle about that issue.