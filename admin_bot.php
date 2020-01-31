<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bot/admin/CommandManager.php';
require_once __DIR__ . '/bot/admin/MessageManager.php';
require_once __DIR__ . '/bot/BaseBot.php';
require_once __DIR__ . '/bot/admin/AdminBot.php';

use DataSet\Bot\Admin\AdminBot;
$botToken = '1078103682:AAHrI5w3VW0nBf0xSo7akF3B1hoD_lZH8NU';
$myBot= new AdminBot($botToken);
$myBot->handle();