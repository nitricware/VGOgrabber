<?php
	
	
	namespace NitricWare;
	
	
	use Exception;
	use SimpleXMLElement;
	use DOMDocument;
	
	class VGOGrabber {
		/** @var \CurlHandle $curl */
		private $curl;
		private string $loginUrl = "https://my.libsyn.com/auth/login";
		private string $feedUrl = "https://vgo.libsyn.com/podcast";
		
		private array $fileHandlers = [];
		/** @var string[] $files */
		private array $files = [];
		
		public function __construct () {
			/*
			 * Initialize CurlHandle
			 */
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
		}
		
		/**
		 * Handles automatic clean-up
		 */
		public function __destruct () {
			curl_close($this->curl);
			foreach ($this->fileHandlers as $fileHandler) {
				fclose($fileHandler);
			}
			
			foreach (scandir("podcasts/") as $cachedFile) {
				if ($cachedFile != "." && $cachedFile != "..") {
					if (!in_array($cachedFile, $this->files)) {
						secho("found $cachedFile which is an old episode. deleting.");
						unlink("podcasts/$cachedFile");
					}
				}
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
		private function makeCURLrequest (string $url, $post = false, bool $return = false, $toFile = false): string {
			if (!$post) {
				$returnTransfer = false;
			} else {
				$returnTransfer = true;
				// TODO escape special characters
				$postFields = "";
				foreach ($post as $key => $value) {
					$post .= "$key=$value";
				}
				
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
			}
			
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->curl, CURLOPT_POST, $returnTransfer);
			
			if ($toFile) {
				$this->files[] = $toFile.".mp3";
				if (!file_exists("podcasts/$toFile.mp3")) {
					if ($this->fileHandlers[$toFile] = fopen("podcasts/$toFile.mp3", 'wb+')) {
						curl_setopt($this->curl, CURLOPT_URL, $url);
						curl_setopt($this->curl, CURLOPT_NOBODY, 0);
						curl_setopt($this->curl, CURLOPT_TIMEOUT, 300);
						curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 1);
						curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
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
			$this->makeCURLrequest($this->loginUrl, ["email" => $email, "password" => $password]);
		}
		
		/**
		 * @return string
		 * @throws Exception
		 */
		public function fetchVGOfeed (): string {
			return $this->makeCURLrequest($this->feedUrl,);
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
			
			//$episodes = $episodesXML->xpath("//div[@class='libsyn-item']");
		}
		
		/**
		 * @param SimpleXMLElement $xml
		 * @param string           $query
		 *
		 * @return SimpleXMLElement[]
		 */
		private function findNodesWithXPath (SimpleXMLElement $xml, string $query) {
			return $xml->xpath($query);
		}
		
		/**
		 * @param string $html
		 *
		 * @return SimpleXMLElement[]
		 */
		public function getEpisodeNodes (string $html) {
			$xml = $this->getSimpleXMLFromHTML($html);
			return $this->findNodesWithXPath($xml, "//div[@class='libsyn-item']");
		}
		
		public function getEpisodeDuration (string $html) {
			$xml = $this->getSimpleXMLFromHTML($html);
			$xpath = $this->findNodesWithXPath($xml, "//span[@class='static-duration']");
			return substr((string)$xpath[0], 2);
		}
		
		public function getPlayerUrl (SimpleXMLElement $episode) {
			return "https:" . (string)$episode->div[2]->iframe["src"];
		}
		
		public function getDirectLink (string $html) {
			// $re = '/"media_url":"([\w\d:\\\\\/\.\-]+)\?dest-id=([0-9]+)"/m';
			$re = '/(traffic\.libsyn\.com[\\\\\/a-zA-Z0-9_\-.]+\.mp3)/m';
			preg_match($re, $html, $matches);
			$directLink = $matches[0];
			return "https://" . str_replace("\/", "/", $directLink);
		}
		
		/**
		 * prints given string, array, object
		 *
		 * @param mixed $log
		 */
		public function log ($log): void {
			echo "<pre>";
			print_r($log);
			echo "</pre>";
		}
	}