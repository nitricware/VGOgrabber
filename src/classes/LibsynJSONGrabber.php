<?php
	
	
	namespace NitricWare;
	
	
	use DateTime;
	use Exception;
	use stdClass;
	
	class LibsynJSONGrabber extends LibsynGrabber {
		private string $baseURL = "https://html5-player.libsyn.com/embed/list/id/%d/size/%d/sort_by_field/%s/sort_by_direction/%s";
		private string $jsonURL;
		/** @var stdClass[] $jsonObject */
		private array $jsonObject;
		
		/** @var Episode[] $episodes */
		private array $episodes;
		
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
					pubDate: new DateTime($episode->release_date),
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
		 * @param LibsynSortByField|string $sortByField
		 */
		public function setSortByField (string|LibsynSortByField $sortByField): void {
			$this->sortByField = $sortByField;
		}
		
		/**
		 * @param LibsynSortByDirection|string $sortByDirection
		 */
		public function setSortByDirection (string|LibsynSortByDirection $sortByDirection): void {
			$this->sortByDirection = $sortByDirection;
		}
		
		/**
		 * @return LibsynSortByField|string
		 */
		public function getSortByField (): string|LibsynSortByField {
			return $this->sortByField;
		}
		
		/**
		 * @return LibsynSortByDirection|string
		 */
		public function getSortByDirection (): string|LibsynSortByDirection {
			return $this->sortByDirection;
		}
		
		/**
		 * @return string
		 */
		public function getJsonURL (): string {
			return $this->jsonURL;
		}
		
		/**
		 * @return Episode[]
		 */
		public function getEpisodes (): array {
			return $this->episodes;
		}
		
	}
	
	abstract class LibsynSortByField {
		const RELEASE_DATE = "release_date";
	}
	
	class LibsynSortByDirection {
		const ASC = "asc";
		const DESC = "desc";
	}