<?php
defined('MUDPUPPY') or die('Restricted');

class Security {

	const PASSWORD_SALT_LENGTH = 8;

	/**
	 * Attempts to log a user in.
	 * @param string $email
	 * @param string $password
	 * @return bool|string true if successful, error message otherwise
	 */
	public static function login($email, $password) {
		self::logout();
		if (strlen($email) == 0 || strlen($password) == 0) {
			return 'Email and Password are required';
		}

        return "Security login method incomplete";
        /*
		$user = User::getByEmail($email);
		if ($user != null) {
			$salt = substr($user->password, 0, self::PASSWORD_SALT_LENGTH);
			$encryptedPass = substr($user->password, self::PASSWORD_SALT_LENGTH);
			if (self::getPasswordHash($salt, $password) == $encryptedPass) {
				Session::setVar('user', $user);
				return true;
			}
		}*/
		return 'Invalid Login or Password';
	}

	public static function refreshLogin() {
		if (self::isLoggedIn()) {
			Session::setVar('user', User::get(self::getUser()->id));
		}
	}

	/**
	 * Gets the current user object.
	 * @return User
	 */
	public static function getUser() {
		return Session::getVar('user');
	}

	/**
	 * Check if user is logged in.
	 * @return boolean
	 */
	public static function isLoggedIn() {
		return Session::isVar('user');
	}

	/**
	 * Logs out the current user by resetting the session.
	 */
	public static function logout() {
		Session::resetAll();
	}

    /**
     * Checks if the current user has a given permission.
     * @param $permission
     * @return bool
     */
    public static function hasPermission($permission) {
        if (is_null($permission) || !self::isLoggedIn()) {
            return false;
        }
        $permissions = self::getUser()->permissions;
        return $permissions && is_array($permissions) && in_array($permission, $permissions);
    }

    /**
     * Checks if the current user has a set of permissions.
     * @param $permission
     * @return bool
     */
    public static function hasPermissions($permissions) {
        if (is_null($permissions) || empty($permissions) || !self::isLoggedIn()) {
            return false;
        }

        $userPermissions = self::getUser()->permissions;
        if (!is_array($permissions))
            return false;

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions))
                return false;
        }
        return true;
    }

	/**
	 * Gets password hash given salt.
	 * @param string $salt
	 * @param string $password
	 * @return string;
	 */
	public static function getPasswordHash($salt, $password) {
		return sha1($salt . $password);
	}

	/**
	 * Generates a salted and hashed password.
	 * @param string $password
	 * @return string
	 */
	public static function encryptPassword($password) {
		static $saltChars = '1234567890abcdef';
		mt_srand((microtime(true) * 1283) ^ 0x3d85cf7);
		$salt = '';
		for ($i = 0; $i < self::PASSWORD_SALT_LENGTH; $i++) {
			$salt .= $saltChars[mt_rand(0, strlen($saltChars) - 1)];
		}
		$encrypted = self::getPasswordHash($salt, $password);
		return $salt . $encrypted;
	}

	/**
	 * Generates a strong random password.
	 * @return string
	 */
	public static function generatePassword() {
		$lower = 'qwertyuiopasdfghjklzxcvbnm';
		$upper = 'ASDFGHJKLZXCVBNMQWERTYUIOP';
		$number = '1234567890';
		$special = '~!@#$%^&*()-_=+{}[]\|;:,<.>/?';
		$pass = '';
		mt_srand(crc32(microtime()));
		$max = strlen($lower) - 1;
		for ($x=0; $x<4; $x++) {
			$pass .= $lower[mt_rand(0, $max)];
		}
		$max = strlen($upper) - 1;
		for ($x=0; $x<4; $x++) {
			$pass .= $upper[mt_rand(0, $max)];
		}
		$max = strlen($number) - 1;
		for ($x=0; $x<2; $x++) {
			$pass .= $number[mt_rand(0, $max)];
		}
		$max = strlen($special) - 1;
		for ($x=0; $x<2; $x++) {
			$pass .= $special[mt_rand(0, $max)];
		}
		return str_shuffle($pass);
	}

}

?>
