<?php

use Dotenv\Dotenv;
use Kottenberg\Telegram;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('BOT_TOKEN', $_ENV['TG_API']);
define('GPT_TOKEN', $_ENV['GPT_API']);
define('WEBHOOK_URL', $_ENV['WEBHOOK_URL']);

const API_URL = 'https://api.telegram.org/bot' . BOT_TOKEN . '/';

$bot = new Telegram(GPT_TOKEN, API_URL);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // receive wrong update, must not happen
    exit;
}

if (isset($update["message"])) {
    $bot->processMessage($update["message"]);
}
