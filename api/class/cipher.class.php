<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class cipher {

		private $_cipher = MCRYPT_RIJNDAEL_128;
		private $_mode = MCRYPT_MODE_CBC;
		private $_key;
		private $_initializationVector;
		private $_initializationVectorSize;

		public function __construct($key, $iVector) {
			// $sha256_32 = hash('SHA256', $key);
			// $this->_key = (strlen($sha256_32) > 32) ? substr($sha256_32, 0, 32):$sha256_32;
			$this->_key = $key;
			$this->_initializationVector = $iVector;
			$this->_initializationVectorSize = mcrypt_get_iv_size($this->_cipher, $this->_mode);

			if (strlen($key) > ($keyMaxLength = mcrypt_get_key_size($this->_cipher, $this->_mode))) {
				throw new InvalidArgumentException("The key length must be less or equal than $keyMaxLength.");
			}
		}

		public function encrypt($data) {
			$blockSize = mcrypt_get_block_size($this->_cipher, $this->_mode);
			$pad = $blockSize - (strlen($data) % $blockSize);
			// $iv = mcrypt_create_iv($this->_initializationVectorSize, MCRYPT_DEV_URANDOM);
			$iv = $this->_initializationVector;
			return base64_encode(mcrypt_encrypt(
				$this->_cipher,
				$this->_key,
				$data . str_repeat(chr($pad), $pad),
				$this->_mode,
				$iv
			));
		}

		public function decrypt($encryptedData) {
			$encryptedData = base64_decode($encryptedData);
			// $initializationVector = substr($encryptedData, 0, $this->_initializationVectorSize);
			$initializationVector = $this->_initializationVector;
			$data =  mcrypt_decrypt(
				$this->_cipher,
				$this->_key,
				// substr($encryptedData, $this->_initializationVectorSize),
				$encryptedData,
				$this->_mode,
				$initializationVector
			);
			$pad = ord($data[strlen($data) - 1]);
			return substr($data, 0, -$pad);
		}

	}

?>