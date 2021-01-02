<?php
	namespace NitricWare;
	
	use DateTime;
	use Exception;
	
	require "classes/Episode.php";
	/** @var string $login_url */
	/** @var string $feed_url */
	/** @var string $email */
	/** @var string $password */
	/** @var string $cache_url */
	/** @var string $fixed_feed_url */
	require "settings.php";
	require "classes/VGOGrabber.php";
	
	$grabber = new VGOGrabber();
	
	function secho(string $text) {
		echo $text."<br />";
	}
	
	secho("VGO Grabber v1.0");
	
	try {
		$grabber->libsynLogin($email, $password);
	} catch (Exception $e) {
		secho ("Unexpected Error...");
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
		secho("Element $i in episodes array - string procedure");
		
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
		$itemDummy = str_replace("{{{DIRECT_LINK}}}", $cache_url.$episodeObject->guid.".mp3", $itemDummy);
		$itemDummy = str_replace("{{{DURATION}}}", $episodeObject->duration, $itemDummy);
		
		try {
			$grabber->getPodcastFile($directLink, $episodeObject->guid);
		} catch (Exception $e) {
			secho("This episode was already downloaded. Skipping.");
		}
		
		if ($i == 0) {
			$feed = str_replace("{{{PUBDATE}}}", $episodeObject->pubDate, $feed);
		}
		
		$items = $items."\n".$itemDummy;
		
		$i++;
	}
	
	$feed = str_replace("{{{ITEMS}}}", $items, $feed);
	$feed = str_replace("{{{FEED_LOCATION}}}", $fixed_feed_url, $feed);
	file_put_contents("tmp/current_feed.xml", $feed);
	secho("new feed created. closing");