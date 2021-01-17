<?php
	
	
	namespace NitricWare;
	
	
	use DateTime;
	
	class LibsynRSSCreator {
		private string $dummyFeedFile;
		private string $episodeDummy;
		private string $finalFeed;
		
		public function __construct (
			/** @var Episode[] $episodes */
			public array $episodes,
			public string $dummyFeedFilePath,
			public string $feedLocation
		) {
			$this->dummyFeedFile = file_get_contents($this->dummyFeedFilePath);
			$this->extractEpisodeDummy();
			$this->fillFeedDummy();
			$this->save();
		}
		
		private function extractEpisodeDummy () {
			$re = '/\{{3}LOOP ITEMS\}{3}([\s\S]*)\{{3}ENDLOOP ITEMS\}{3}/m';
			preg_match($re, $this->dummyFeedFile, $episodeDummy);
			$this->episodeDummy = $episodeDummy[1];
			$this->dummyFeedFile = str_replace(
				"{{{LOOP ITEMS}}}" . $this->episodeDummy . "{{{ENDLOOP ITEMS}}}",
				"{{{ITEMS_PLACEHOLDER}}}",
				$this->dummyFeedFile
			);
		}
		
		private function fillFeedDummy () {
			$now = new DateTime();
			$this->finalFeed = str_replace("{{{FEED_LOCATION}}}", $this->feedLocation, $this->dummyFeedFile);
			$this->finalFeed = str_replace("{{{PUBDATE}}}", $now->format("D, j M Y h:i:s +0000"), $this->finalFeed);
			$this->finalFeed = str_replace("{{{ITEMS_PLACEHOLDER}}}", $this->fillEpisodeDummies(), $this->finalFeed);
		}
		
		private function fillEpisodeDummy (Episode $episode): string {
			$dummyCopy = str_replace("{{{TITLE}}}", $episode->title, $this->episodeDummy);
			$dummyCopy = str_replace("{{{PUBDATE}}}", $episode->pubDate->format("D, j M Y h:i:s +0000"), $dummyCopy);
			$dummyCopy = str_replace("{{{GUID}}}", $episode->guid, $dummyCopy);
			$dummyCopy = str_replace("{{{DESCRIPTION}}}", $episode->description, $dummyCopy);
			$dummyCopy = str_replace("{{{DIRECT_LINK}}}", $episode->directLink, $dummyCopy);
			$dummyCopy = str_replace("{{{DURATION}}}", $episode->duration, $dummyCopy);
			$dummyCopy = str_replace("{{{FILESIZE}}}", $episode->fileSize, $dummyCopy);
			
			return $dummyCopy;
		}
		
		private function fillEpisodeDummies (): string {
			$return = "";
			foreach ($this->episodes as $episode) {
				$return .= $this->fillEpisodeDummy($episode) . "\n";
			}
			return $return;
		}
		
		private function save (): void {
			$info = pathinfo($this->dummyFeedFilePath);
			file_put_contents("exports/" . $info["basename"], $this->finalFeed);
		}
		
		/**
		 * @return string
		 */
		public function getFinalFeed (): string {
			return $this->finalFeed;
		}
	}