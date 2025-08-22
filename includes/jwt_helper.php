<?php
// Minimal JWT implementation using firebase/php-jwt
// You must run: composer require firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

class JwtHelper {
    private static $secret = 'REPLACE_THIS_WITH_A_RANDOM_SECRET_KEY'; // Change this!
    private static $algo = 'HS256';

    public static function generate($payload, $expSeconds = 1800) {
        $issuedAt = time();
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $issuedAt + $expSeconds;
        return JWT::encode($payload, self::$secret, self::$algo);
    }

    public static function validate($jwt) {
        try {
            $decoded = JWT::decode($jwt, new Key(self::$secret, self::$algo));
            return (array)$decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}
