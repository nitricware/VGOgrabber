# VGOgrabber

Downloads - if premium credentials are provided - any number of premium episodes from the VGO podcast and offers a new RSS feed.

## Usage

1. get VGO premium subscription
2. get webspace with a minimum of 100 MB (average size of 1 episode) storage and PHP 8.0 installed
3. modify `settings.example.php` and save as `settings.php`:

```php
/**
 * Your libsyn/VGO premium credentials.
 * Special characters are escaped by the software automatically.
 */
 const EMAIL = "";
 const PASSWORD = "";
 
/**
 * PODCAST_FILE_PATH must be the url where your downloaded episodes
 * are stored. It's the path to index.php with an appended
 * podcasts/
 * i.e. https://yourserver.com/pathWhereIndexIs/podcasts/
 * 
 * FEED_LOCATION must be the url where the feed create by
 * this software is stored. It's the path to index.php with
 * tmp/current_feed.xml appended.
 * i.e. https://yourserver.com/pathWhereIndexIs/tmp/current_feed.xml
 */
 
 const PODCAST_FILE_PATH = "";
 const FEED_LOCATION = "";
 
 /**
 * SLUG is @deprecated for the JSON variant of the software.
 * However, the HTML variant still relies on this constant.
 * For VGO the slug would be vgo.
 * 
 * The JSON variant of the software uses the SHOW_ID constant
 * to fetch the feed. For VGO, SHOW_ID would be 58049.
 */
 
 const SLUG = "";
 const SHOW_ID = 0;
 
 /**
 * EPISODE_LIMIT specifies the number of episodes this software
 * downloads. Reduce to save disk space.
 */

 const EPISODE_LIMIT = 10;
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
* 1.1 - LibsynGrabber class implemented
* 1.2 - updated README, bugfixes, verbose output
* 2.0 - requires PHP 8, deprecated HTML parser, implemented JSON parser, change in file structure, option to download any number of episodes (yes a complete mirror is now possible), filesize is now in the feed, support for different libsyn podcasts

## Known Issues and Limitations

- terrible UI
- bad error management
- Any content that is not VGO but free (Game Store Guy, Community Show) will not show up in this feed. There is no feasible way to fix it. I talked to Michelle. Premium Previes that are directly connected to VGO (Micro VGO Premium Preview) will show up in the feed.