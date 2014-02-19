<?php
	defined('MUDPUPPY') or die('Restricted');

	class JSPressModule extends Module {
		
		public function getRequiredPermissions($method, $input) {
			return array();
		}
		
		public function req_get() {
			session_write_close();		// don't need the session
			
			// check for cache
			$type = Request::get('type', 'js');
			$folders = Request::getArray('files', array());
			$cacheFile = 'app/cache/'.$type.'Cache-'.md5(APP_VERSION.implode(',', $folders)).".$type";
			$data = NULL;
			$gzData = NULL;
			
			if (file_exists($cacheFile)) {
				$mTime = filemtime($cacheFile);
				// check if js files are modified only once per 60 sec
				if ($mTime+60 < time()) {
					$ok = true;
					$fileList = self::getFilesInFolders($folders);
					foreach ($fileList as $file) {
						if (filemtime($file) > $mTime) {
							$ok = false;
							break;
						}
					}
					if ($ok) {
						// no modified files, touch mtime on cache
						touch($cacheFile);
						touch($cacheFile . '.gz');
					} else {
						// found modified files, generate a new cache file
						// generate minified JS/css and write to cache file
						if ($type == 'js')
							$data = self::generateMinifiedJS($fileList);
						else
							$data = self::generateMinified($fileList);
						$gzData = gzencode($data);
						file_put_contents($cacheFile, $data);
						file_put_contents($cacheFile.'.gz', $gzData);
						file_put_contents($cacheFile.'.cachetime', '');
					}
				}
			} else {
				$fileList = self::getFilesInFolders($folders);
				// generate minified JS/css and write to cache file
				if ($type == 'js')
					$data = self::generateMinifiedJS($fileList);
				else
					$data = self::generateMinified($fileList);
				$gzData = gzencode($data);
				file_put_contents($cacheFile, $data);
				file_put_contents($cacheFile.'.gz', $gzData);
				file_put_contents($cacheFile.'.cachetime', '');
			}
			
			// check if not modified
			$lastModifiedTime = filemtime($cacheFile.'.cachetime');
			$etag = md5($cacheFile.$lastModifiedTime);
			if ($lastModifiedTime && (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModifiedTime || 
			    trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
			    File::addExpirationHeaders('', 7*24*60*60, $lastModifiedTime, $etag);
			    header("HTTP/1.1 304 Not Modified"); 
			} else {
				// check if the browser accepts gzip encoding. Most do, but just in case
				if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
					header('Content-Encoding: gzip');
					self::outputFile($cacheFile . '.gz', $lastModifiedTime, $etag, $type);
				} else {
					self::outputFile($cacheFile, $lastModifiedTime, $etag, $type);
				}
			}

			flush();
				
			// clean up if necessary
			self::cleanCache();
			
			App::cleanExit(true);
		}
		
		private function req_getCSS() {
			
		}
		
		private static function outputFile($filename, $lastModified, $etag, $type) {
			
			// set content type
			switch ($type) {
				case 'js':
					header("Content-type: text/javascript");
					break;
				case 'css':
					header("Content-type: text/css");
					break;
				default:
					header('Content-type: text/plain');
			}
			
			// set cache settings
			File::addExpirationHeaders('', 7*24*60*60, $lastModified, $etag);
			
			header("Content-Length: " . filesize($filename));
			readfile($filename);
			flush();
		}

		private static function generateMinifiedJS($fileList) {
			$js = '';
			foreach ($fileList as $file) {
				$js .= JSMin::minify(file_get_contents($file)) . "\n";
			}
			return $js;
		}

		private static function generateMinified($fileList) {
			$data = '';
			foreach ($fileList as $file) {
				$data .= preg_replace('#\\s{2,}|\\r+|\\n+|/\\*.*?\\*/#s', ' ', file_get_contents($file)) . "\n";
			}
			return $data;
		}
		
		private static function getFilesInFolders($folders) {
			$fileList = array();
			foreach ($folders as $folder) {
				if (is_dir($folder)) {
					if (strlen($folder) == 0 || $folder[strlen($folder)-1] != '/')
						$folder .= '/';
					$files = File::getFileList($folder, false, true, false, '#.*\.js$#');
					foreach ($files as $file) {
						$fileList[] = $folder . $file;
					}
				} else {
					$fileList[] = $folder;		// add single file
				}
			}
			return $fileList;
		}
		
		public static function printScriptTags($folders, $type="js", $dir='', $attributes=array()) {
			$fileList = self::getFilesInFolders($folders);
			
			$attributesText = '';
			foreach ($attributes as $key=>$value) {
				$attributesText .= " $key=\"$value\"";
			}
			
			// in debug load all js files individually
			if (Config::$debug) {
				foreach ($fileList as $file) {
					if ($type == 'js') {
						print '	<script type="text/javascript" src="'.$file."\"$attributesText></script>\n";
					} else if ($type == 'css') {
						print '	<link rel="stylesheet" type="text/css" href="'.$file."\"$attributesText />\n";
					}
				}
			} else {
				// in production (not debug) load all js files from cache, minified & gzipped
				$folderList = "";
				foreach ($folders as $folder) {
					if (is_dir($folder)) {
						if (strlen($folder) == 0 || $folder[strlen($folder)-1] != '/')
							$folder .= '/';
					}
					$folderList .= "&files[]=".urlencode($folder);
				}
				if ($type == 'js') {
					print '<script type="text/javascript" src="'.$dir.'?mod=JSPress&req=get'.$folderList."&type=js&v=".APP_VERSION."\"$attributesText></script>\n";
				} else if ($type == 'css') {
					print '<link rel="stylesheet" type="text/css" href="'.$dir.'?mod=JSPress&req=get'.$folderList."&type=css&v=".APP_VERSION."\"$attributesText></script>\n";
				}
			}
		}
		
		public static function cleanCache() {
			// clean up ~once out of every 1000 cache loads
			if (rand(0,1000) != 0)
				return;
				
			$files = File::getFileList('app/cache/', false, true, false, '#^.*Cache-.*?\.(js|css)$#');
			foreach ($files as $file) {
				$file = 'app/cache/'.$file;
				// remove all files untouched for more than 7 days
				if (filemtime($file) < time()-60*60*24*7) {
					unlink($file);
					unlink($file.'.gz');
					unlink($file.'.cachetime');
				}
			}
		}
	}
?>