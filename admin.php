<?php
require_once __DIR__ . '/vendor/autoload.php';
use Nette\Caching\Storages\FileStorage;
use Telegram\Bot\Api;

$servername = "localhost";
$db_username = "id12318158_admin";
$password = "admin";
$db_name = "id12318158_my_bot";
$botToken = '1078103682:AAHrI5w3VW0nBf0xSo7akF3B1hoD_lZH8NU';

$getCommand = (object)['command' => "/get", "label" => "Get audio"];
$correctCommand = (object)['command' => "/correct", "label" => "Correct"];
$wrongCommand = (object)['command' => "/wrong", "label" => "Wrong"];
$approveCommand = (object)['command' => "/approve", "label" => "Approve"];
$rejectCommand = (object)['command' => "/reject", "label" => "Reject"];
$voteAndValidationCommands = [
    $correctCommand->command, $correctCommand->label, $wrongCommand->command, $wrongCommand->label,
    $approveCommand->command, $approveCommand->label, $rejectCommand->command, $rejectCommand->label,];
$validationCommands = [$approveCommand->command, $approveCommand->label, $rejectCommand->command, $rejectCommand->label];
$voteCommands = [$correctCommand->command, $correctCommand->label, $wrongCommand->command, $wrongCommand->label];

$help = "$getCommand->command - get new text to validate";
$noAudioToValidate = "Sorry, there is not audio to validate";
$firstGetAnAudioToValidate = "First get an audio to validate. $getCommand->command";
$noAccessToValidate = "You don't have access to validate this audio. \t\nYou can just vote (Is it correct audio or not)";

$voteKeyboard = json_encode([
    'keyboard' => [[$wrongCommand->label, $correctCommand->label]],
    'resize_keyboard' => true,
    'one_time_keyboard' => true
]);
$validateKeyboard = json_encode([
    'keyboard' => [[$rejectCommand->label, $approveCommand->label]],
    'resize_keyboard' => true,
    'one_time_keyboard' => true
]);
$getKeyboard = json_encode([
    'keyboard' => [[$getCommand->label]],
    'resize_keyboard' => true,
    'one_time_keyboard' => true
]);

$bot = new Api($botToken);
$update = $bot->getWebhookUpdates();
$message = $update['message'];
$chatId = $message['chat']['id'];
$username = $message['from']['username'];
$userId = $message['from']['id'];
$storage = new FileStorage('temp');

$database = mysqli_connect($servername, $db_username, $password, $db_name);
mysqli_query($database, "SET NAMES utf8");
$users = mysqli_query($database, "SELECT * FROM bot_users WHERE id=" . $userId . " And is_admin=1");
$user = mysqli_fetch_assoc($users);
mysqli_free_result($users);

$currentValidatingAudioCacheKey = $userId . ".currentValidatingText";
$laseValidatedTextCacheKey = $userId . ".lastValidatedText";

$lastValidatedTextCache = $storage->read($laseValidatedTextCacheKey);

try {
    if ($text = $message['text']) {
        if ($text == "/start" || $text == $getCommand->command || $text == $getCommand->label) {
            $audios = $user == null ?
                mysqli_query($database, "SELECT a.*, t.text, (a.`reject_amount` + a.`approved_amount`) AS votes 
 FROM audios a, texts t WHERE a.`text_id`=t.`id` AND a.is_validated = 0 AND a.`speaker_id` != $userId ORDER BY votes LIMIT 1") :
                mysqli_query($database, "SELECT a.*, t.text, (a.`reject_amount` + a.`approved_amount`) AS votes 
 FROM audios a, texts t WHERE a.`text_id`=t.`id` AND a.is_validated = 0 ORDER BY votes LIMIT 1");
            $audio = mysqli_fetch_assoc($audios);
            mysqli_free_result($audios);
            if ($audio) {
                $keyboard = ($user == null ? $voteKeyboard : $validateKeyboard);
                $bot->sendVoice(['chat_id' => $chatId,
                    'voice' => 'http://my-telgram-bot.000webhostapp.com/audios/' . $audio['audio_path'],
                    'reply_markup' => $keyboard]);
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $audio['text']]);
                $storage->write($currentValidatingAudioCacheKey, $audio, []);
            } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $noAudioToValidate,
                "reply_markup" => json_encode(["remove_keyboard" => true])]);
        } else if (in_array($text, $voteAndValidationCommands)) {
            if ($currentValidatingAudioCache = $storage->read($currentValidatingAudioCacheKey)) {
                if (in_array($text, $validationCommands) && $user == null)
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $noAccessToValidate, 'reply_markup' => $voteKeyboard]);
                else {
                    if ($text == $correctCommand->command || $text == $correctCommand->label) {
                        $newAmount = $currentValidatingAudioCache["approved_amount"] + 1;
                        $audioId = $currentValidatingAudioCache["id"];
                        mysqli_query($database, "UPDATE `audios` SET `approved_amount` = $newAmount WHERE id = $audioId");
                    } elseif ($text == $wrongCommand->command || $text == $wrongCommand->label) {
                        $newAmount = $currentValidatingAudioCache["reject_amount"] + 1;
                        $audioId = $currentValidatingAudioCache["id"];
                        mysqli_query($database, "UPDATE `audios` SET `reject_amount` = $newAmount WHERE id = $audioId");
                    } elseif ($text == $approveCommand->command || $text == $approveCommand->label) {
                        mysqli_query($database, "UPDATE `audios` SET `is_validated` = 1 WHERE id = " . $currentValidatingAudioCache["id"]);
                        if (!file_exists('audios/approved/' . $currentValidatingAudioCache['speaker_id']))
                            mkdir('audios/approved/' . $currentValidatingAudioCache['speaker_id']);
                        rename("audios/" . $currentValidatingAudioCache['audio_path'], "audios/approved/" . $currentValidatingAudioCache['audio_path']);
                    } elseif ($text == $rejectCommand->command || $text == $rejectCommand->label) {
                        mysqli_query($database, "DELETE FROM `audios` WHERE id = " . $currentValidatingAudioCache["id"]);
                        unlink("audios/" . $currentValidatingAudioCache['audio_path']);
                    }

                    $audios = $user == null ?
                        mysqli_query($database, "SELECT a.*, t.text, (a.`reject_amount` + a.`approved_amount`) AS votes 
 FROM audios a, texts t WHERE a.`text_id`=t.`id` AND a.is_validated = 0 AND a.`speaker_id` != $userId ORDER BY votes LIMIT 1") :
                        mysqli_query($database, "SELECT a.*, t.text, (a.`reject_amount` + a.`approved_amount`) AS votes 
 FROM audios a, texts t WHERE a.`text_id`=t.`id` AND a.is_validated = 0 ORDER BY votes LIMIT 1");
                    $audio = mysqli_fetch_assoc($audios);
                    mysqli_free_result($audios);
                    if ($audio) {
                        $keyboard = ($user == null ? $voteKeyboard : $validateKeyboard);
                        $bot->sendVoice(['chat_id' => $chatId,
                            'voice' => 'http://my-telgram-bot.000webhostapp.com/audios/' . $audio['audio_path'],
                            'reply_markup' => $keyboard]);
                        $bot->sendMessage(['chat_id' => $chatId, 'text' => $audio['text']]);
                        $storage->write($currentValidatingAudioCacheKey, $audio, []);
                    } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $noAudioToValidate, "reply_markup" => json_encode(["remove_keyboard" => true])]);
                }
            } else
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $firstGetAnAudioToValidate, 'reply_markup' => $getKeyboard]);
        } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $help, 'reply_markup' => $getKeyboard]);
    } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $help, 'reply_markup' => $getKeyboard]);
} catch (Exception $exception) {
    $bot->sendMessage(['chat_id' => $chatId, 'text' => $exception->getMessage()]);
} finally {
    mysqli_close($database);
}