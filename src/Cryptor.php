<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class Cryptor
 * @package Yoder\YIPS
 */
class Cryptor
{
	private static $key = '6gc;pfVaEN5.8c2H_d4E!N_!qC}FkX)B';
	/**
	 * Encrypt a message.
	 *
	 * @param string $message
	 * @return string
	 */
	public static function encrypt($message)
	{
		$key = self::$key;
		$nonce = random_bytes(
			SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
		);

		$cipher = base64_encode(
			$nonce .
				sodium_crypto_secretbox(
					$message,
					$nonce,
					$key
				)
		);
		sodium_memzero($message);
		sodium_memzero($key);
		return $cipher;
	}

	/**
	 * Decrypt a message.
	 *
	 * @param string $encrypted
	 * @return string
	 */
	public static function decrypt($encrypted)
	{
		$key = self::$key;
		$decoded = base64_decode($encrypted);
		if ($decoded === false) {
			throw new \Exception(__('Encoding failed', 'yips-customization'));
		}
		if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
			throw new \Exception(__('The message was truncated', 'yips-customization'));
		}
		$nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
		$ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

		$plain = sodium_crypto_secretbox_open(
			$ciphertext,
			$nonce,
			$key
		);
		if ($plain === false) {
			throw new \Exception(__('The message was tampered with in transit', 'yips-customization'));
		}
		sodium_memzero($ciphertext);
		sodium_memzero($key);
		return $plain;
	}
}
