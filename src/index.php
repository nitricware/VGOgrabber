<?php
	namespace NitricWare;
	
	use DateTime;
	use Exception;
	
	ob_end_flush();
	ob_implicit_flush(true);
	
	require "classes/Episode.php";
	/** @var string $login_url */
	/** @var string $feed_url */
	/** @var string $cache_url */
	/** @var string $fixed_feed_url */
	require "settings.php";
	require "classes/LibsynGrabber.php";
	
	$grabber = new LibsynGrabber(SLUG);
	
	/**
	 * @param mixed $thing
	 * @param bool  $verbose
	 */
	function secho ($thing, bool $verbose = false): void {
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		
		echo "<pre>";
		if ($verbose) {
			echo $caller['file'] . " (" . $caller['line'] . ")\t\t";
		}
		print_r($thing);
		echo "<br />";
		echo "</pre>";
	}
	
	secho("VGO Grabber v1.1.1");
	
	try {
		$grabber->libsynLogin(EMAIL, PASSWORD);
	} catch (Exception $e) {
		secho($e->getMessage());
		exit();
	}
	
	//TODO implement check for unsuccessful login
	
	secho("login done.");
	
	try {
		$episodesHTML = $grabber->fetchVGOfeed();
	} catch (Exception $e) {
		secho("Unexpected Error...");
		exit();
	}
	
	secho("got episode list html");
	
	$episodes = $grabber->getEpisodeNodes($episodesHTML);
	
	$feed = file_get_contents("tmp/feed_dummy.xml");
	
	$items = "";
	
	$i = 0;
	
	foreach ($episodes as $episode) {
		secho("Element $i of " . count($episodes));
		
		/**
		 * load player
		 */
		$playerURL = $grabber->getPlayerUrl($episode);
		
		try {
			$player = $grabber->fetchPlayer($playerURL);
		} catch (Exception $e) {
			secho("Unexpected Error...");
			exit();
		}
		
		$duration = $grabber->getEpisodeDuration($player);
		
		/**
		 * get direct link to mp3
		 */
		
		$directLink = $grabber->getDirectLink($player);
		
		/**
		 * get pubDate
		 */
		
		$pubDay = (string)trim($episode->div[1]);
		try {
			$pubDate = new DateTime($pubDay);
			$pubDateString = $pubDate->format("D, j M Y h:i:s +0000");
		} catch (Exception $e) {
			$pubDateString = "Mon, 1 Jan 2000 00:00:00 +0000";
		}
		
		$episodeObject = new Episode();
		$episodeObject->title = (string)$episode->div[0]->a;
		$episodeObject->link = (string)$episode->div[0]->a["href"];
		$episodeObject->description = (string)$episode->div[3]->p;
		$episodeObject->pubDate = $pubDateString;
		$episodeObject->duration = $duration;
		$episodeObject->guid = sha1($episodeObject->link);
		$episodeObject->directLink = $directLink;
		
		$itemDummy = file_get_contents("tmp/item_dummy.xml");
		$itemDummy = str_replace("{{{TITLE}}}", $episodeObject->title, $itemDummy);
		$itemDummy = str_replace("{{{PUBDATE}}}", $episodeObject->pubDate, $itemDummy);
		$itemDummy = str_replace("{{{GUID}}}", $episodeObject->guid, $itemDummy);
		$itemDummy = str_replace("{{{LINK}}}", $episodeObject->link, $itemDummy);
		$itemDummy = str_replace("{{{DESCRIPTION}}}", $episodeObject->description, $itemDummy);
		$itemDummy = str_replace("{{{DIRECT_LINK}}}", $cache_url . $episodeObject->guid . ".mp3", $itemDummy);
		$itemDummy = str_replace("{{{DURATION}}}", $episodeObject->duration, $itemDummy);
		secho(">>> $episodeObject->title");
		try {
			secho("Starting download");
			$grabber->getPodcastFile($directLink, $episodeObject->guid);
			secho("Download done");
		} catch (Exception $e) {
			secho("Exception: " . $e->getMessage());
		}
		
		if ($i == 0) {
			$feed = str_replace("{{{PUBDATE}}}", $episodeObject->pubDate, $feed);
		}
		
		$items = $items . "\n" . $itemDummy;
		
		$i++;
		secho("--------------------------------------------------------");
	}
	
	$feed = str_replace("{{{ITEMS}}}", $items, $feed);
	$feed = str_replace("{{{FEED_LOCATION}}}", $fixed_feed_url, $feed);
	file_put_contents("tmp/current_feed.xml", $feed);
	secho("new feed created. closing");
	$grabber->cleanPodcastDirectory();