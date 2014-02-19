<?php
// copyright 2008 Levi Lansing
defined('MUDPUPPY') or die('Restricted');

class File {

	function __construct() {
		throw new Execption('File is a static class; cannot instantiate.');
	}

	/**
	 * removes ../ and ./ and excessive /
	 * optionally verifies the file falls within a given base path
	 *
	 * @param $file string
	 * @param $base string
	 * @return string
	 */
	static function cleanPath($file, $base = null) {
		$file = str_replace('\\', '/', $file);
		$file = preg_replace(array('#\.\./|\./|/+#', '#[<>\?:\*"\|]+#'), array('/', ''), $file);
		if ($base) {
			// verify we are within the requried base
			if (strncmp($file, $base, strlen($base)) != 0) {
				return $base;
			}
		}
		if (strlen($file) == 0) {
			return "./";
		}
		return $file;
	}

	static function cleanFileName($file) {
		$file = str_replace('\\', '/', $file);
		// remove path & invalid characters
		$file = preg_replace(array('#^.*/|[<>\?:\*"\|]#'), '', $file);
		return $file;
	}

	static function getTitle($filename, $bIncludeExt = true) {
		$filename = self::cleanFileName($filename);

		if ($bIncludeExt) {
			return $filename;
		}

		$i = strrpos($filename, ".");
		if ($i == false) {
			return null;
		}
		return substr($filename, 0, $i);
	}

	static function getExtension($filename, $bToLower = true) {
		$i = strrpos($filename, ".");
		if ($i !== false) {
			$ext = substr($filename, $i + 1);
			if ($bToLower) {
				return strtolower($ext);
			}
			return $ext;
		}
		return "";
	}

	static function hasValidExtension($file) {
		return self::isValidExtension(self::getExtension($file));
	}

	static function isValidExtension($ext) {
		$allowed = array('css', 'gif', 'jpeg', 'jpg', 'js', 'txt', 'png', 'mp3');
		return in_array(strtolower($ext), $allowed);
	}

	static function getMimeType($file) {
		//$finfo = finfo_open(FILEINFO_MIME);
		//$mime = finfo_file($finfo, $file);
	}

	static function passthrough($file, $expires = 86400, $attachment = false, $deleteAfterDownload = false) {
		$file = self::cleanPath($file);
		if (file_exists($file) && !is_dir($file) && is_readable($file)) {
			$ext = self::getExtension($file);

			if (self::isRestricted($ext)) {
				App::cleanExit();
			} // unrecognized extension

			$mimes = array(
				'gif' => 'image/gif',
				'png' => 'image/png',
				'jpeg' => 'image/jpg',
				'jpg' => 'image/jpg',
				'css' => 'text/css',
				'js' => 'text/javascript',
				'txt' => 'text/plain',
				'pdf' => 'application/pdf',
				'exe' => 'application/octet-stream',
				'zip' => 'application/zip',
				'doc' => 'application/msword',
				'xls' => 'application/vnd.ms-excel',
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'ppt' => 'application/vnd.ms-powerpoint'
			);

			if (isset($mimes[$ext])) {
				header('content-type: ' . $mimes[$ext]);
			} else {
				header('content-type: application/force-download');
			}

			if ($attachment) {
				if (is_string($attachment)) {
					$filename = $attachment;
				} else {
					$filename = addslashes(self::getTitle($file, true));
				}
				header("Content-Disposition: attachment;filename=\"$filename\"");
			}

			self::addExpirationHeaders($file, $expires);
			$fp = fopen($file, 'r');
			fpassthru($fp);
			fclose($fp);

			if ($deleteAfterDownload) {
				ob_end_flush();
				ob_start();
				try {
					unlink($file);
				} catch (Exception $e) {
				}
				ob_end_clean();
			}
		}

		App::cleanExit();
	}

	/**
	 * check if a file exists
	 * supports local urls from site root (beginning with a /) that reference real filenames
	 * @param $file
	 */
	static function exists($file) {
		if (strlen($file) > 0 && $file[0] == '/') {
			$rfile = Config::$docroot . substr($file, 1);
			if (file_exists($rfile)) {
				return true;
			}
		} else if (file_exists($file)) {
			return true;
		}

		// remove any leading /'s and any get parameters
		$file = preg_replace('#(^\/+)|(\?.*$)#', '', $file);
		return file_exists($file);
	}

	/**
	 * @param $extension string - must be lowercase
	 * @return bool
	 */
	static function isRestricted($extension) {
		$restricted = array('php', 'cgi', 'pl', 'html', 'htm');
		return in_array($extension, $restricted);
	}

	static function addExpirationHeaders($file = '', $sec = 86400, $lmod = null, $etag = null) {
		header('Pragma:'); // clear this incase the host already set it (dreamhost)
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $sec) . ' GMT');
		header('Cache-Control: max-age=' . $sec);

		if (strlen($file) > 0 && is_null($lmod)) {
			$lmod = @filemtime($file);
		} else if (is_null($lmod)) {
			$lmod = time();
		}
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lmod) . " GMT");

		if (is_null($etag) && strlen($file) > 0) {
			$etag = md5($file);
		}
		if (!is_null($etag)) {
			header("Etag: $etag");
		}
	}

	// get file list.  filter is a preg expression. does NOT sort files / folders
	static function getFileList($root, $bRecursive = false, $bIncludeFiles = true, $bIncludeDirs = false, $filter = '', $bIncludeHidden = false, $dir = '', $level = 0) {
		if ($root[strlen($root) - 1] != "/") {
			$root .= '/';
		}

		if (file_exists($root . $dir) && $handle = opendir($root . $dir)) {
			$files = array();
			while (false !== ($file = @readdir($handle))) {
				// ignore hidden folders ".*" (and "..")
				// unless $bIncludeHidden is true, then only ignore "." and "..*"
				if ($file[0] != '.' || ($bIncludeHidden && (strlen($file) > 1 && $file[1] != '.'))) {
					if (is_dir($root . $dir . $file)) {
						if ($bRecursive) {
							$files = array_merge($files, self::getFileList($root, $bRecursive, $bIncludeFiles, $bIncludeDirs, $filter, $bIncludeHidden, $dir . $file . '/', $level + 1));
						} else if ($bIncludeDirs) {
							$files[] = $dir . $file;
						}
					} else if ($bIncludeFiles) {
						$files[] = $dir . $file;
					}
				}
			}
			closedir($handle);

			if ($filter != '') {
				$files = preg_grep($filter, $files);
			}

			$result = array();
			if ($bIncludeDirs && $level > 0) {
				$result[] = $dir;
			}
			foreach ($files as $f) {
				$result[] = $f;
			}
			return $result;
		}
		return array();
	}

	// get file tree.  filter is a preg expression
	// uses TreeNode for the tree structure
	// automatically sorts listings
	static function getFileTree($root, $bRecursive = false, $bIncludeFiles = true, $bIncludeDirs = false, $filter = '', $dir = '', $level = 0, $tree = null) {
		if ($root[strlen($root) - 1] != "/") {
			$root .= '/';
		}

		if ($tree == null) {
			$tree = new TreeNode('');
		}

		if (file_exists($root . $dir) && $handle = opendir($root . $dir)) {
			// first get list of all folders and files
			$tempFolders = array();
			$tempFiles = array();
			while (false !== ($file = @readdir($handle))) {
				if ($file[0] != '.') // ignore hidden folders ".*" (and "..")
				{
					if (is_dir($root . $dir . $file)) {
						$tempFolders[] = $file;
					} else {
						$tempFiles[] = $file;
					}
				}
			}

			// now sort the lists
			sort($tempFolders);
			sort($tempFiles);

			// now create the tree from sorted lists
			foreach ($tempFolders as $f) {
				if ($bRecursive) {
					$folder = new TreeNode($f, $dir . $f . '/');
					self::getFileTree($root, $bRecursive, $bIncludeFiles, $bIncludeDirs, $filter, $dir . $f . '/', $level + 1, $folder);
					if ($bIncludeDirs || $folder->hasChildren()) {
						$tree->addChild($folder);
					}
				} else if ($bIncludeDirs) {
					if ($filter == '' || preg_match($filter, $dir . $f)) {
						$tree->addChild(new TreeNode($f, $dir . $f . '/'));
					}
				}
			}

			if ($bIncludeFiles) {
				foreach ($tempFiles as $file) {
					if ($filter == '' || preg_match($filter, $dir . $file)) {
						$tree->addChild(new TreeNode($file, $dir . $file));
					}
				}
			}
			closedir($handle);
		}
		return $tree;
	}

	// a safe putContents
	// attempts to write to a temporary file, and then rename it to $filename to avoid concurrency problems
	static function putContents($filename, $contents) {
		$tempName = tempnam(dirname(__FILE__), 'fpc_temp');
		$f = @fopen($tempName, 'wb');
		if (!$f) {
			$tempName = tempnam(dirname(__FILE__), uniqid('fpc_temp'));
			$f = @fopen($tempName, 'wb');
		}
		if (!$f) {
			trigger_error("file::putContents() : error opening temporary file for writing ($tempName). Falling back to file_put_contents()", E_USER_WARNING);
			if (@file_put_contents($filename, $contents)) {
				return true;
			}
			trigger_error("file::putContents() : error file_put_contents returned false for '$filename'", E_USER_WARNING);
			return false;
		}

		fwrite($f, $contents);
		fclose($f);

		if (!@rename($tempName, $filename)) {
			@unlink($filename);
			if (!@rename($tempName, $filename)) {
				trigger_error("file::putContents() : error renaming temp file '$tempName' to '$filename'. it may be write protected.", E_USER_WARNING);
				@unlink($tempName);
				return false;
			}
		}
		return true;
	}

	static function getUploadErrorMessage($code) {
		if ($code == UPLOAD_ERR_OK) {
			return '';
		}

		$uploadErrors = array(
			UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
			UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
			UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
		);
		if (isset($uploadErrors[$code])) {
			return $uploadErrors[$code];
		}
		return 'An unknown error has occured.';
	}

	static function getUniqueFilename($basePath, $filename) {
		static $chrs = '1234567890abcdefghijklmnopqrstuvwxyz';
		mt_srand((microtime(true) * 35421) ^ 0x5E7484D9);
		$hash = '';
		do {
			$hash = '';
			for ($i = 0; $i < 16; $i++) {
				$hash .= $chrs[mt_rand(0, strlen($chrs) - 1)];
			}

			$basePath = preg_replace('#\/+$#', '', $basePath) . '/';
		} while (file_exists($basePath . $hash . '-' . $filename));

		return $hash . '-' . $filename;
	}
}

?>