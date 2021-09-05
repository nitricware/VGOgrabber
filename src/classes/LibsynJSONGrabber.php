<?php
	
	
	namespace NitricWare;
	
	
	use DateTime;
	use Exception;
	use stdClass;
	
	/**
	 * Class LibsynJSONGrabber
	 *
	 * @package NitricWare
	 */
	class LibsynJSONGrabber extends LibsynGrabber {
		private string $baseURL = "https://html5-player.libsyn.com/embed/list/id/%d/size/%d/sort_by_field/%s/sort_by_direction/%s";
		private string $jsonURL;
		/** @var stdClass[] $jsonObject */
		private array $jsonObject;
		
		/** @var Episode[] $episodes */
		private array $episodes;
		
		/**
		 * LibsynJSONGrabber constructor.
		 *
		 * @param string                       $podcastFileLocation
		 * @param int                          $showID
		 * @param int                          $limit
		 * @param LibsynSortByField|string     $sortByField
		 * @param LibsynSortByDirection|string $sortByDirection
		 */
		public function __construct (
			private string $podcastFileLocation,
			private int $showID = 0,
			private int $limit = 2,
			private LibsynSortByField|string $sortByField = LibsynSortByField::RELEASE_DATE,
			private LibsynSortByDirection|string $sortByDirection = LibsynSortByDirection::DESC
		) {
			parent::__construct();
			
			$this->jsonURL = sprintf($this->baseURL, $this->showID, $this->limit, $this->sortByField, $this->sortByDirection);
		}
		
		/**
		 * @throws Exception
		 */
		public function __destruct () {
			parent::__destruct();
		}
		
		/**
		 * @throws Exception
		 */
		public function getJSONFeed (): void {
			$jsonRAW = $this->makeCURLrequest(url: $this->jsonURL);
			$this->jsonObject = json_decode($jsonRAW);
		}
		
		/**
		 * @throws Exception
		 */
		public function getEpisodesFromJSON () {
			foreach ($this->jsonObject as $episode) {
				$episodeObject = new Episode(
					title: $episode->item_title,
					link: $episode->permalink_url,
					description: $episode->item_subtitle,
					pubDate: DateTime::createFromFormat('M/d/Y', $episode->release_date),
					duration: $episode->duration,
					ext: $episode->ext,
					podcastFilePath: $this->podcastFileLocation,
					downloadLink: $episode->download_link
				);
				$this->episodes[] = $episodeObject;
			}
		}
		
		/**
		 * @throws Exception
		 */
		public function downloadEpisodes () {
			for ($i = 0; $i < count($this->episodes); $i++) {
				$this->files[] = $this->episodes[$i]->guid . "." . $this->episodes[$i]->ext;
				$filePath = "podcasts/" . $this->episodes[$i]->guid . "." . $this->episodes[$i]->ext;
				if (!file_exists($filePath)) {
					$this->getPodcastFile($this->episodes[$i]->downloadLink, $this->episodes[$i]->guid);
				}
				$this->episodes[$i]->fileSize = filesize($filePath);
			}
		}
		
		/**
		 * @return Episode[]
		 */
		public function getEpisodes (): array {
			return $this->episodes;
		}
		
	}
	
	/**
	 * Class LibsynSortByField
	 * ENUM-equivalent
	 *
	 * @package NitricWare
	 */
	abstract class LibsynSortByField {
		const RELEASE_DATE = "release_date";
	}
	
	/**
	 * Class LibsynSortByDirection
	 * ENUM-equivalent
	 *
	 * @package NitricWare
	 */
	abstract class LibsynSortByDirection {
		const ASC = "asc";
		const DESC = "desc";
	}
