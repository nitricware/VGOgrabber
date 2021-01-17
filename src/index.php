<?php
	namespace NitricWare;
	
	use Exception;
	
	require "classes/Episode.php";
	require "settings.php";
	require "classes/LibsynGrabber.php";
	require "classes/LibsynJSONGrabber.php";
	require "classes/LibsynRSSCreator.php";
	
	/**
	 * @param mixed $thing
	 * @param bool  $verbose
	 */
	function secho (mixed $thing, bool $verbose = false): void {
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
	
	$grabber = new LibsynJSONGrabber(
		podcastFileLocation: PODCAST_FILE_PATH,
		showID: SHOW_ID,
		limit: EPISODE_LIMIT);
	
	try {
		$grabber->libsynLogin(EMAIL, PASSWORD);
	} catch (Exception $e) {
		secho($e->getMessage());
		exit();
	}
	
	secho("Login done.");
	
	try {
		$grabber->getJSONFeed();
	} catch (Exception $e) {
		secho("ERROR" . $e->getMessage());
		exit();
	}
	
	secho("JSON feed fetched.");
	
	try {
		$grabber->getEpisodesFromJSON();
	} catch (Exception $e) {
		secho("ERROR" . $e->getMessage());
		exit();
	}
	
	secho("Episodes parsed");
	
	try {
		$grabber->downloadEpisodes();
	} catch (Exception $e) {
		secho("ERROR" . $e->getMessage());
	}
	
	secho("Episodes downloaded");

	new LibsynRSSCreator(
		episodes: $grabber->getEpisodes(),
		dummyFeedFilePath: "feedTemplates/vgo_feed.xml",
		feedLocation: FEED_LOCATION
	);

	
	secho("Feed created.");