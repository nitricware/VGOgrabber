<?php
	
	
	namespace NitricWare;
	
	
	class Episode {
		public string $title;
		public string $link;
		public string $description;
		public string $pubDate;
		public string $duration;
		public string $guid;
		public string $directLink;
		public int $size;
		
		public function __construct (
			string $title = "",
			string $link = "",
			string $description = "",
			string $pubDate = "",
			string $duration = "",
			string $guid = "",
			string $directLink = "",
			int $size = 0
		) {
			$this->title = $title;
			$this->link = $link;
			$this->description = $description;
			$this->pubDate = $pubDate;
			$this->duration = $duration;
			$this->guid = $guid;
			$this->directLink = $directLink;
			$this->size = $size;
		}
	}