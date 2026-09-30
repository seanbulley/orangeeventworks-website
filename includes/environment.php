<?php
/**
 * Shared runtime environment for production and demo.
 *
 * Production:
 *   /public_html/                  -> orangeeventworks-config.php
 *
 * Demo:
 *   /public_html/demo/             -> orangeeventworks-demo-config.php
 *
 * Both private config files live one level above public_html.
 */
declare(strict_types=1);

$siteRoot = dirname(__DIR__);
$isDemo = basename($siteRoot) === 'demo';
$siteBase = $isDemo ? '/demo' : '';
$assetBase = $siteBase . '/assets';

$privateRoot = $isDemo ? dirname($siteRoot, 2) : dirname($siteRoot);
$configFile = $privateRoot . '/' . ($isDemo
    ? 'orangeeventworks-demo-config.php'
    : 'orangeeventworks-config.php');

if ($isDemo && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
}

if (!is_file($configFile) || !is_readable($configFile)) {
    http_response_code(500);
    error_log('ORANGE EventWorks configuration file is missing or unreadable: ' . $configFile);
    exit('Website configuration error.');
}

$config = require $configFile;

if (!is_array($config)) {
    http_response_code(500);
    error_log('ORANGE EventWorks configuration file did not return an array.');
    exit('Website configuration error.');
}

$timezone = (string)($config['app']['timezone'] ?? 'Europe/London');
date_default_timezone_set($timezone);
