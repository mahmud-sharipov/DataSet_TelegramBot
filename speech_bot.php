<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bot/speech/CommandManager.php';
require_once __DIR__ . '/bot/speech/MessageManager.php';
require_once __DIR__ . '/bot/BaseBot.php';
require_once __DIR__ . '/bot/speech/SpeechBot.php';

use DataSet\Bot\Speech\SpeechBot;

$botToken = '1095346825:AAFyNchseaf-Wo6Qvwon49SefkQzrCHtH0I';
$myBot= new SpeechBot($botToken);
$myBot->handle();