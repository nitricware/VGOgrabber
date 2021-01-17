<?php
	
	
	namespace NitricWare;
	
	
	use DOMDocument;
	use Exception;
	use SimpleXMLElement;
	
	class LibsynHTMLGrabber extends LibsynGrabber {
		private string $feedUrlBase = "https://%s.libsyn.com/podcast";
		private string $feedUrl;
		
		public function __construct (string $slug = "") {
			parent::__construct();
			$this->slug = $slug;
			$this->feedUrl = sprintf($this->feedUrlBase, $slug);
		}
		
		/**
		 * @return string
		 * @throws Exception
		 */
		public function fetchVGOfeed (): string {
			return $this->makeCURLrequest($this->feedUrl);
		}
		
		/**
		 * @param string $playerUrl
		 *
		 * @return string
		 * @throws Exception
		 */
		public function fetchPlayer (string $playerUrl): string {
			return $this->makeCURLrequest($playerUrl);
		}
		
		/**
		 * @param string $html
		 *
		 * @return SimpleXMLElement
		 */
		private function getSimpleXMLFromHTML (string $html): SimpleXMLElement {
			$dom = new DOMDocument();
			@$dom->loadHTML($html);
			
			return simplexml_import_dom($dom);
		}
		
		/**
		 * @param SimpleXMLElement $xml
		 * @param string           $query
		 *
		 * @return SimpleXMLElement[]
		 */
		private function findNodesWithXPath (SimpleXMLElement $xml, string $query): array {
			return $xml->xpath($query);
		}
		
		/**
		 * @param string $html
		 *
		 * @return SimpleXMLElement[]
		 */
		public function getEpisodeNodes (string $html): array {
			$xml = $this->getSimpleXMLFromHTML($html);
			return $this->findNodesWithXPath($xml, "//div[@class='libsyn-item']");
		}
		
		/**
		 * @param string $html
		 *
		 * @return false|string
		 */
		public function getEpisodeDuration (string $html) {
			$xml = $this->getSimpleXMLFromHTML($html);
			$xpath = $this->findNodesWithXPath($xml, "//span[@class='static-duration']");
			return substr((string)$xpath[0], 2);
		}
		
		/**
		 * @param SimpleXMLElement $episode
		 *
		 * @return string
		 */
		public function getPlayerUrl (SimpleXMLElement $episode): string {
			return "https:" . (string)$episode->div[2]->iframe["src"];
		}
		
		/**
		 * @param string $html
		 *
		 * @return string
		 */
		public function getDirectLink (string $html): string {
			// $re = '/"media_url":"([\w\d:\\\\\/\.\-]+)\?dest-id=([0-9]+)"/m';
			$re = '/(traffic\.libsyn\.com[\\\\\/a-zA-Z0-9_\-.]+\.mp3)/m';
			preg_match($re, $html, $matches);
			$directLink = $matches[0];
			return "https://" . str_replace("\/", "/", $directLink);
		}
	}