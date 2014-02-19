<?php
defined('MUDPUPPY') or die('Restricted');

class SmartObject {
	private $_data;

	public function SmartObject($assocArray) {
		if (empty($assocArray)) {
			$this->_data = array();
		} else {
			$this->_data = $assocArray;
		}
	}

	public function getData() {
		return $this->_data;
	}

	// "operator overloading" //

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		}
		return null;
	}

	public function __set($key, $value) {
		$this->_data[$key] = $value;
	}

	public function __unset($key) {
		unset($this->_data[$key]);
	}

	public function __isset($key) {
		return isset($this->_data[$key]);
	}

	public function encodeJSON() {
		return json_encode($this->_data);
	}
}

?>