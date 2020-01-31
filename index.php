    <?php
    require_once __DIR__ . '/vendor/autoload.php';
    use Nette\Caching\Storages\FileStorage;
    use Telegram\Bot\Api;

    $servername  = "localhost";
    $db_username = "id12318158_admin";
    $password    = "admin";
    $db_name     = "id12318158_my_bot";
    $botToken = '1095346825:AAFyNchseaf-Wo6Qvwon49SefkQzrCHtH0I';

    $getCommand = (object)['command' => "/get", "icon" => "⮚"];
    $againCommand = (object)['command' => "/again", 'icon' => "⟲"];
    $commands = Array('/start', '/help', $getCommand->command, $againCommand->command);

    $getAndRepeatKeyboard = json_encode([
        'keyboard' => [[$againCommand->icon, $getCommand->icon]],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ]);
    $getKeyboard = json_encode([
        'keyboard' => [[$getCommand->icon]],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ]);
    $genderKeyboard = json_encode([
        'keyboard' => [["Мард", "Зан"]],
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ]);

    /*MESSAGES*/
    $start_newUser = "Салом, \t\nТашакур ба шумо Шумо, ки мехоҳед саҳми худро дар ин лоиҳа гузоред. \t\nХоҳишмандам ба саволҳои зерин посух диҳед: \t\nНоми шумо?";
    $askAge = "Сину сол?";
    $wrongAge = "Сину соли шумо бояд адат бутун ва фосилаи аз 7 то 90 бошад!";
    $askGender = "Ҷинс? (Мард, Зан)";
    $wrongGender = "Ҷинс бояд ки 'Мард' ё 'Зан' бошвд";
    $informationFullyEntered = "Ташакур!";
    $firstEnterYourInfo = "Лутфат аввал ба саволҳо ҷавоб медодед! \t\nИн маълумотҳо хело зарур мебошад.";
    $sendOnlyVoiceMessage = "Танҳо сабти овозро ирсол кунед";
    $firstSelectTex = "Аввал матнро дархост кунед!";
    $audioRecorded = "Ташакур, (матни навбати - $getCommand->command , такроран мабт кардан - $againCommand->command";
    $help = "/get-Манти навбати  \t\n/again-Такроран сабт кардани овоз(танҳо дар ҳолате ки Шумо барои ягон мант овоз сабт кардаед!)";
    $textInfo = "Матн: <b>%s</b>\t\n Давомнокии сабт: <b>%s</b> сония";
    $anyTextNotRecordedYet = "Шумо ҳоло барои ягон мант овоз сабт накардаед. $getCommand->command - матни навбати";
    $cannotDownloadAudioFile = "Хатогӣ ҳангоми сабти маълумот. Лутфан бори дигар такрор кунед. \t\nАгар ин хабар такрор шуда бошад  ба @shamah_mahmud муроҷиат кунед.";
    /*END MESSAGES*/

    $bot = new Api($botToken);
    $message = $bot->getWebhookUpdates()['message'];
    $chatId = $message['chat']['id'];
    $username = $message['from']['username'];
    $userId = $message['from']['id'];
    $storage = new FileStorage('temp');

    $database = mysqli_connect($servername, $db_username, $password, $db_name);
    mysqli_query($database, "SET NAMES utf8");
    $users = mysqli_query($database, "SELECT * FROM bot_users WHERE id=" . $userId);
    $user = mysqli_fetch_assoc($users);
    mysqli_free_result($users);

    $creationCacheKey = $userId . ".creation";
    $currentRecordingTextCacheKey = $userId . ".currentRecordingText";
    $laseRecordedTextCacheKey = $userId . ".lastRecordedText";
    $creationCache = $storage->read($creationCacheKey);
    $currentRecordingTextCache = $storage->read($currentRecordingTextCacheKey);
    $lastRecordedTextCache = $storage->read($laseRecordedTextCacheKey);

    try {
        $text = $message['text'];
        if ($text) {
            if ($creationCache != null && in_array($text, $commands))
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $firstEnterYourInfo]);
            elseif ($text == "/start" || ($user == null && $creationCache == null)) {
                if ($user == null) {
                    $storage->write($creationCacheKey, array('status' => 'started'), []);
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $start_newUser]);
                } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $help, 'reply_markup' => $getKeyboard]);
            } elseif ($text == "/help") $bot->sendMessage(['chat_id' => $chatId, 'text' => $help, 'reply_markup' => $getAndRepeatKeyboard]);
            elseif ($text == $getCommand->command || $text == $getCommand->icon) {
                $index = rand(0, 100);
                $texts = mysqli_query($database, "SELECT `id`, `text`, `expected_time` FROM  texts ORDER BY audio_amount LIMIT " . $index . ",1");
                $db_text = mysqli_fetch_assoc($texts);
                mysqli_free_result($texts);
                $bot->sendMessage([
                    'parse_mode' => "HTML",
                    'chat_id' => $chatId,
                    'text' => sprintf($textInfo, $db_text['text'], $db_text['expected_time']),
                    "reply_markup"=>json_encode(["remove_keyboard" => true])]);

                $storage->write($currentRecordingTextCacheKey, $db_text, []);
            } elseif ($text == $againCommand->command || $text == $againCommand->icon) {
                if ($lastRecordedTextCache == null)
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $anyTextNotRecordedYet, 'reply_markup' => $getKeyboard]);
                else {
                    $bot->sendMessage([
                        'parse_mode' => "HTML",
                        'chat_id' => $chatId,
                        'text' => sprintf($textInfo, $lastRecordedTextCache['text'], $lastRecordedTextCache['expected_time']),
                        "reply_markup"=>json_encode(["remove_keyboard" => true])]);
                    $storage->write($currentRecordingTextCacheKey, $lastRecordedTextCache, []);
                }
            } else {
                if ($creationCache != null) {
                    if (!isset($creationCache['name'])) {
                        $creationCache['name'] = $text;
                        $storage->write($creationCacheKey, $creationCache, []);
                        $bot->sendMessage(['chat_id' => $chatId, 'text' => $askAge]);
                    } elseif (!isset($creationCache['age'])) {
                        $age = intval($text);
                        if ($age > 6 && $age < 91) {
                            $creationCache['age'] = $age;
                            $storage->write($creationCacheKey, $creationCache, []);
                            $bot->sendMessage(['chat_id' => $chatId, 'text' => $askGender, 'reply_markup' =>$genderKeyboard]);
                        } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $wrongAge]);
                    } else {
                        if ($text == "Мард" || $text == "Зан") {
                            $speakerSql = "INSERT INTO speakers (`id`, `name`, `age`, `gender`) VALUE(" . $userId . ",'" . $creationCache['name'] . "'," . $creationCache['age'] . ",'" . $text . "')";
                            $userSql = "INSERT INTO bot_users (`id`,`username`) VALUE(" . $userId . ",'" . $username . "')";
                            mysqli_query($database, $speakerSql);
                            mysqli_query($database, $userSql);

                            $storage->remove($creationCacheKey);
                            $user = ["id" => $userId, "username" => $username];
                            $bot->sendMessage(['chat_id' => $chatId, 'text' => $informationFullyEntered . "\t\n" . $help, 'reply_markup' => $getKeyboard]);
                        } else $bot->sendMessage(['chat_id' => $chatId, 'text' => $wrongGender, 'reply_markup' => $genderKeyboard]);
                    }
                } else if ($currentRecordingTextCache != null)
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $sendOnlyVoiceMessage]);
                else
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $help, 'reply_markup' => $getAndRepeatKeyboard]);
            }
        } else if ($voice = $message['voice']) {
            if ($currentRecordingTextCache == null)
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $firstSelectTex, 'reply_markup' => $getKeyboard]);
            else {
                $fileInfo = $bot->getFile(['file_id' => $voice['file_id']]);
                $url = "https://api.telegram.org/file/bot" . $botToken . "/" . $fileInfo['file_path'];

                $userAudioDir = 'audios/' . $userId;
                if (!file_exists($userAudioDir)) mkdir($userAudioDir);

                $audioFielName = $currentRecordingTextCache['id'] . "_" . time() . ".oga";
                if (file_put_contents($userAudioDir . "/" . $audioFielName, fopen($url, 'r'))) {
                    $audioSql = "INSERT INTO audios (`speaker_id`, `text_id`, `audio_path`) 
                                      VALUE(" . $userId . "," . $currentRecordingTextCache['id'] . ",'" . $userId . "/" . $audioFielName . "')";
                    mysqli_query($database, $audioSql);
                    $storage->remove($currentRecordingTextCacheKey);
                    $storage->write($laseRecordedTextCacheKey, $currentRecordingTextCache, []);
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $audioRecorded, 'reply_markup' => $getAndRepeatKeyboard]);
                } else
                    $bot->sendMessage(['chat_id' => $chatId, 'text' => $cannotDownloadAudioFile, 'reply_markup' => $getAndRepeatKeyboard]);
            }
        } else {
            if ($storage->read($creationCacheKey) != null)
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $firstEnterYourInfo]);
            else if ($currentRecordingTextCache != null)
                $bot->sendMessage(['chat_id' => $chatId, 'text' => $sendOnlyVoiceMessage]);
        }
    } catch (Exception $exception) {
        $bot->sendMessage(['chat_id' => $chatId, 'text' => $exception->getMessage()]);
    } finally {
        mysqli_close($database);
    }