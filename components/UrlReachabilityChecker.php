<?php

namespace app\components;

use yii\validators\UrlValidator;

class UrlReachabilityChecker
{
    private const CONNECT_TIMEOUT = 8;
    private const TOTAL_TIMEOUT = 15;
    private const USER_AGENT = 'Mozilla/5.0 (compatible; ShortLinkLocalCheck/1.0; +local)';

    public function validateFormat(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return false;
        }

        $validator = new UrlValidator([
            'validSchemes' => ['http', 'https'],
            'defaultScheme' => null,
            'enableIDN' => extension_loaded('intl'),
        ]);

        if (!$validator->validate($url)) {
            return false;
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        return true;
    }

    public function isReachable(string $url): bool
    {
        if (function_exists('curl_init')) {
            return $this->reachableViaCurl($url);
        }

        return $this->reachableViaStream($url);
    }

    private function reachableViaCurl(string $url): bool
    {
        $code = $this->curlRequest($url, true);
        if ($this->isHttpSuccess($code)) {
            return true;
        }
        if ($code === 405 || $code === 501) {
            $code = $this->curlRequest($url, false);
        }

        return $this->isHttpSuccess($code);
    }

    private function curlRequest(string $url, bool $headOnly): int
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return 0;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::TOTAL_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => self::USER_AGENT,
        ];

        if ($headOnly) {
            $options[CURLOPT_NOBODY] = true;
        } else {
            $options[CURLOPT_HTTPGET] = true;
            $options[CURLOPT_RANGE] = '0-2047';
        }

        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            return 0;
        }

        return $httpCode;
    }

    private function isHttpSuccess(int $code): bool
    {
        if ($code <= 0) {
            return false;
        }
        if ($code >= 500) {
            return false;
        }
        if ($code === 404 || $code === 410) {
            return false;
        }

        return true;
    }

    private function reachableViaStream(string $url): bool
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => self::TOTAL_TIMEOUT,
                'follow_location' => 1,
                'max_redirects' => 10,
                'header' => 'User-Agent: ' . self::USER_AGENT . "\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $headers = @get_headers($url, 0, $ctx);
        if ($headers === false) {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => self::TOTAL_TIMEOUT,
                    'follow_location' => 1,
                    'max_redirects' => 10,
                    'header' => "User-Agent: " . self::USER_AGENT . "\r\nRange: bytes=0-2047\r\n",
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $headers = @get_headers($url, 0, $ctx);
        }

        if ($headers === false || !isset($headers[0])) {
            return false;
        }

        if (preg_match('/\s(\d{3})\s/', $headers[0], $m)) {
            return $this->isHttpSuccess((int) $m[1]);
        }

        return false;
    }
}
