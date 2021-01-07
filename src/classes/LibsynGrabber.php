<?php
	
	
	namespace NitricWare;
	
	
	use Exception;
	use SimpleXMLElement;
	use DOMDocument;
	
	class LibsynGrabber {
		/** @var \CurlHandle $curl */
		private $curl;
		private string $loginUrl = "https://my.libsyn.com/auth/login";
		private string $feedUrlBase = "https://%s.libsyn.com/podcast";
		private string $feedUrl = "";
		
		private array $fileHandlers = [];
		/** @var string[] $files */
		private array $files = [];
		
		public function __construct (string $slug) {
			/*
			 * Initialize CurlHandle
			 */
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
			
			$this->feedUrl = sprintf($this->feedUrlBase, $slug);
			
			if (!file_exists("podcasts/")) {
				mkdir("podcasts/");
			}
		}
		
		/**
		 * Handles automatic clean-up
		 */
		public function __destruct () {
			curl_close($this->curl);
			foreach ($this->fileHandlers as $fileHandler) {
				fclose($fileHandler);
			}
		}
		
		/**
		 * @param string      $url
		 * @param bool|array  $post
		 * @param bool        $return
		 * @param bool|string $toFile
		 *
		 * @return string|void
		 * @throws Exception
		 */
		private function makeCURLrequest (string $url, $post = false, bool $return = true, $toFile = false) {
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, $return);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
			
			if ($post) {
				curl_setopt($this->curl, CURLOPT_POST, true);
				$postFieldsArray = [];
				foreach ($post as $key => $value) {
					$postFieldsArray[] = "$key=$value";
				}
				
				$postFields = implode("&", $postFieldsArray);
				
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
			} else {
				curl_setopt($this->curl, CURLOPT_POST, false);
			}
			
			if ($toFile) {
				$this->files[] = $toFile . ".mp3";
				if (!file_exists("podcasts/$toFile.mp3")) {
					if ($this->fileHandlers[$toFile] = fopen("podcasts/$toFile.mp3", 'wb+')) {
						curl_setopt($this->curl, CURLOPT_URL, $url);
						curl_setopt($this->curl, CURLOPT_NOBODY, 0);
						curl_setopt($this->curl, CURLOPT_TIMEOUT, 300);
						curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 1);
						curl_setopt($this->curl, CURLOPT_FILE, $this->fileHandlers[$toFile]);
					}
				} else {
					throw new Exception("This file already exists.");
				}
			}
			
			if ($return) {
				return curl_exec($this->curl);
			} else {
				curl_exec($this->curl);
			}
		}
		
		/**
		 * @param string $email
		 * @param string $password
		 *
		 * @throws Exception
		 */
		public function libsynLogin (string $email, string $password): void {
			$login = $this->makeCURLrequest(
				$this->loginUrl,
				[
					"email" => urlencode($email),
					"password" => urlencode($password),
					"submit" => true
				]);
			
			if (!strpos($login, "Active subscriptions")) {
				throw new Exception("Invalid e-mail address or password provided");
			}
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
		 * @param string $url
		 * @param string $guid
		 *
		 * @throws Exception
		 */
		public function getPodcastFile (string $url, string $guid) {
			$this->makeCURLrequest($url, false, false, $guid);
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
		public function getPlayerUrl (SimpleXMLElement $episode) {
			return "https:" . (string)$episode->div[2]->iframe["src"];
		}
		
		/**
		 * @param string $html
		 *
		 * @return string
		 */
		public function getDirectLink (string $html) {
			// $re = '/"media_url":"([\w\d:\\\\\/\.\-]+)\?dest-id=([0-9]+)"/m';
			$re = '/(traffic\.libsyn\.com[\\\\\/a-zA-Z0-9_\-.]+\.mp3)/m';
			preg_match($re, $html, $matches);
			$directLink = $matches[0];
			return "https://" . str_replace("\/", "/", $directLink);
		}
		
		/**
		 * Deletes episodes that are not in the feed anymore.
		 */
		public function cleanPodcastDirectory () {
			foreach (scandir("podcasts/") as $cachedFile) {
				if ($cachedFile != "." && $cachedFile != "..") {
					if (!in_array($cachedFile, $this->files)) {
						unlink("podcasts/$cachedFile");
					}
				}
			}
		}
	}