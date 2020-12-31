<?php
	namespace NitricWare;
	
	use DateTime;
	use DOMDocument;
	
	require "classes/Episode.php";
	require "settings.php";
	
	function secho(string $text) {
		echo $text."<br />";
	}
	
	secho("VGO Grabber v1.0");
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $login_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt(
		$curl,
		CURLOPT_POSTFIELDS,
		"email=$email&password=$password&submit=true"
	);
	curl_setopt($curl, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
	curl_setopt($curl, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
	
	/**
	 * first, log in
	 */
	curl_exec($curl);
	
	secho("login done.");
	
	curl_setopt($curl, CURLOPT_URL, $feed_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 0);
	
	$episodesHTML = curl_exec($curl);
	
	secho("got episode list html");
	
	$episodesDOM = new DOMDocument();
	@$episodesDOM->loadHTML($episodesHTML);
	
	$episodesXML = simplexml_import_dom($episodesDOM);
	
	$episodes = $episodesXML->xpath("//div[@class='libsyn-item']");
	
	$feed = file_get_contents("tmp/feed_dummy.xml");
	
	$items = "";
	$files = [];
	$fileHandlers = [];
	
	$i = 0;
	
	foreach ($episodes as $episode) {
		secho("Element $i in episodes array - string procedure");
		$episode->saveXML("tmp/test.xml");
		
		/**
		 * load player
		 */
		$playerURL = "https:".(string)$episode->div[2]->iframe["src"];
		
		curl_setopt($curl, CURLOPT_URL, $playerURL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 0);
		
		$player = curl_exec($curl);
		
		$playerDOM = new DOMDocument();
		@$playerDOM->loadHTML($player);
		
		$playerXML = simplexml_import_dom($playerDOM);
		
		$duration =	substr((string)$playerXML->xpath("//span[@class='static-duration']")[0],2);

		/**
		 * get direct link to mp3
		 */
		
		// $re = '/"media_url":"([\w\d:\\\\\/\.\-]+)\?dest-id=([0-9]+)"/m';
		$re = '/(traffic\.libsyn\.com[\\\\\/a-zA-Z0-9_\-.]+\.mp3)/m';
		preg_match($re,$player, $matches);
		$directLink = $matches[0];
		$directLink = "https://".str_replace("\/","/",$directLink);
		
		/**
		 * get pubDate
		 */
		
		$pubDay = (string)trim($episode->div[1]);
		try {
			$pubDate = new DateTime($pubDay);
			// Wed, 23 Dec 2020 04:21:41 +0000
			$pubDateString = $pubDate->format("D, j M Y h:i:s +0000");
		} catch (\Exception $e) {
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
		
		$files[] = $episodeObject->guid.".mp3";
		if (!file_exists("podcasts/$episodeObject->guid.mp3")) {
			if ($fileHandlers[$episodeObject->guid] = fopen("podcasts/$episodeObject->guid.mp3", 'wb+')) {
				secho("file $episodeObject->guid.mp3 does not exist. downloading.");
				curl_setopt($curl, CURLOPT_URL, $directLink);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, 0);
				curl_setopt($curl, CURLOPT_NOBODY, 0);
				curl_setopt($curl, CURLOPT_TIMEOUT, 300);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_FILE, $fileHandlers[$episodeObject->guid]);
				
				curl_exec($curl);
			}
		} else {
			secho("file $episodeObject->guid.mp3 does exits - skipping");
		}
		
		if ($i == 0) {
			$feed = str_replace("{{{PUBDATE}}}", $episodeObject->pubDate, $feed);
		}
		
		$items = $items."\n".$itemDummy;
		
		$i++;
	}
	
	/**
	 * clean up podcasts directory
	 */
	foreach (scandir("podcasts/") as $cachedFile) {
		if ($cachedFile != "." && $cachedFile != "..") {
			if (!in_array($cachedFile, $files)) {
				secho("found $cachedFile which is an old episode. deleting.");
				unlink("podcasts/$cachedFile");
			}
		}
	}
	
	$feed = str_replace("{{{ITEMS}}}", $items, $feed);
	file_put_contents("tmp/current_feed.xml", $feed);
	secho("new feed created. closing");
	curl_close($curl);
	foreach ($fileHandlers as $fileHandler) {
		fclose($fileHandler);
	}