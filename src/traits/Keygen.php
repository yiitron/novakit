<?php

namespace yiitron\novakit\traits;

use Yii;


trait Keygen
{
	public function pkHash($id, $module = null)
	{
		$module = $module ?? (Yii::$app instanceof \yii\web\Application && Yii::$app->controller ? Yii::$app->controller->module->id : $module);
		$key = hash_hmac('sha256', md5($id), sha1(Yii::$app->id . '/' . $module));
		return sprintf('%x', crc32($key));
	}
	public  function cryptID($numerical = false, $randandomStringLength = 15)
	{
		$randomString = $this->password($randandomStringLength, true, 'lud');
		$s = uniqid($randomString, true);
		$hex = bin2hex(substr($s, 0, 5));
		$dec = substr($s, -6) + date('Ym');
		$unique = base_convert($hex, 16, 36) . base_convert($dec, 10, 36);

		if ($numerical) {
			$string = ltrim(crc32($unique . time()), '-');
		} else {
			$string = $unique;
			$i = 0;
			$strlen = strlen($string);
			while ($i < $strlen) {
				$tmp = $string[$i];
				if (rand() % 2 == 0) $tmp = strtoupper($tmp);
				else $tmp = strtolower($tmp);
				if ($i == rand(0, $strlen)) {
					$tmp = ($i % 2 == 0) ? '_' : '-';
				}
				$string[$i] = $tmp;
				$i++;
			}
		}
		return $string;
	}
	public  function generatePassword($length = 8, $add_dashes = true, $available_sets = 'luds')
	{
		$sets = array();
		if (strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if (strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if (strpos($available_sets, 'd') !== false)
			$sets[] = '0123456789';
		if (strpos($available_sets, 's') !== false)
			$sets[] = '!@#$%&*?_=+\:,./^|~<>{}[];"';

		$all = '';
		$password = '';
		foreach ($sets as $set) {
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for ($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];

		$password = str_shuffle($password);

		if (!$add_dashes)
			return $password;

		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while (strlen($password) > $dash_len) {
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}
}
