<?php
	
	
	namespace NitricWare;
	
	
	use CurlHandle;
	use Exception;
	
	class LibsynGrabber {
		/** @var CurlHandle $curl */
		private $curl;
		private string $loginUrl = "https://my.libsyn.com/auth/login";
		private string $logoutUrl = "https://my.libsyn.com/auth/logout";
		
		private array $fileHandlers = [];
		/** @var string[] $files */
		protected array $files = [];
		
		public function __construct (
			public string $slug = "",
		) {
			/*
			 * Initialize CurlHandle
			 */
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
			
			if (!file_exists("podcasts/")) {
				mkdir("podcasts/");
			}
			if (!file_exists("exports/")) {
				mkdir("exports/");
			}
		}
		
		/**
		 * Handles automatic clean-up
		 *
		 * @throws Exception
		 */
		public function __destruct () {
			$this->libsynLogout();
			secho($this->files);
			$this->cleanPodcastDirectory();
			curl_close($this->curl);
			foreach ($this->fileHandlers as $fileHandler) {
				fclose($fileHandler);
			}
		}
		
		/**
		 * @param string      $url
		 * @param bool|array  $post
		 * @param bool|string $toFile
		 *
		 * @return string
		 * @throws Exception
		 */
		protected function makeCURLrequest (string $url, $post = false, $toFile = false): string {
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
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
				if ($this->fileHandlers[$toFile] = fopen("podcasts/$toFile.mp3", 'wb+')) {
					curl_setopt($this->curl, CURLOPT_URL, $url);
					curl_setopt($this->curl, CURLOPT_NOBODY, 0);
					curl_setopt($this->curl, CURLOPT_TIMEOUT, 300);
					curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 1);
					curl_setopt($this->curl, CURLOPT_FILE, $this->fileHandlers[$toFile]);
				}
			}
			
			return curl_exec($this->curl);
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
		 * @throws Exception
		 */
		private function libsynLogout (): void {
			$this->makeCURLrequest($this->logoutUrl);
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
		
		/**
		 * @param string $url
		 * @param string $guid
		 *
		 * @throws Exception
		 */
		public function getPodcastFile (string $url, string $guid) {
			$this->makeCURLrequest($url, false, $guid);
		}
	}