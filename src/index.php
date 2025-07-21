<?php
	namespace NitricWare;
	
	use Exception;
	
	require "classes/Episode.php";
	require "settings.php";
	require "classes/LibsynGrabber.php";
	require "classes/LibsynJSONGrabber.php";
	require "classes/LibsynRSSCreator.php";

    const BASE_DIR = __DIR__;
	
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
		@sflush();
	}
	
	/**
	 * Sends the buffer content to the browser immediately.
	 */
	function sflush(): void {
		flush();
		ob_flush();
	}
	
	$grabber = new LibsynJSONGrabber(
		podcastFileLocation: PODCAST_FILE_PATH,
		showID: SHOW_ID,
		limit: EPISODE_LIMIT);
	
	secho("Welcome to LibsynGrabber.");
    secho("by Kurt Frey aka NitricWare");
	secho("Grabbing feed. To change settings, alter settings.php");
	
	try {
		$grabber->libsynLogin(EMAIL, PASSWORD);
	} catch (Exception $e) {
		secho($e->getMessage());
		exit();
	}
	
	secho("Login done.");
	
	try {
		$grabber->getJSONFeed();
		// secho($grabber->getJSONURL());
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