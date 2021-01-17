<?php
	
	
	namespace NitricWare;
	
	
	use DateTime;
	
	class Episode {
		public string $guid;
		public int $fileSize = 0;
		public string $directLink;
		public int $size;
		
		public function __construct (
			public string $title,
			public string $link,
			public string $description,
			public DateTime $pubDate,
			public string $duration,
			public string $ext,
			private string $podcastFilePath,
			public string $downloadLink
		) {
			$this->guid = sha1($this->pubDate->format("d-m-y h:i") . $this->title);
			$this->directLink = $this->podcastFilePath . $this->guid . "." . $this->ext;
		}
	}