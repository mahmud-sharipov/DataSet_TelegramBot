<?php

namespace DataSet\Bot\Speech;

use DataSet\Bot\BaseBot;
use Exception;
use Nette\Caching\Storages\FileStorage;

class SpeechBot extends BaseBot
{
    protected $creationCacheKey;
    protected $currentRecordingTextCacheKey;
    protected $laseRecordedTextCacheKey;
    protected $creationCache;
    protected $currentRecordingTextCache;
    protected $lastRecordedTextCache;
    protected $speaker;

    public function __construct($apiToken)
    {
        parent::__construct($apiToken);
        $this->storage = new FileStorage('temp/speech');
        $this->creationCacheKey = $this->userId . ".creation";
        $this->currentRecordingTextCacheKey = $this->userId . ".currentRecordingText";
        $this->laseRecordedTextCacheKey = $this->userId . ".lastRecordedText";
        $this->creationCache = $this->storage->read($this->creationCacheKey);
        $this->currentRecordingTextCache = $this->storage->read($this->currentRecordingTextCacheKey);
        $this->lastRecordedTextCache = $this->storage->read($this->laseRecordedTextCacheKey);
        $this->speaker = $this->getSpeaker();
    }

    public function handle()
    {
        try {
            if ($text = $this->message['text'])
                $this->handleTextMessage($text);
            else if ($voice = $this->message['voice'])
                $this->handleVoiceMessage($voice);
            else
                $this->handleOtherMessages();
        } catch (Exception $exception) {
            $this->sendMessage($exception->getMessage());
        } finally {
            $this->dispose();
        }
    }

    function handleTextMessage($text)
    {
        if ($this->creationCache != null && in_array($text, CommandManager::getCommands()))
            $this->sendMessage(MessageManager::get_firstEnterYourInfo() . "1");
        else if ($this->speaker == null && $this->creationCache == null) {
            $this->storage->write($this->creationCacheKey, array('status' => 'started'), []);
            $this->sendMessage(MessageManager::get_startNewUser());
        } elseif ($text == "/start")
            $this->sendMessage([MessageManager::get_help(), MessageManager::get_getKeyboard()]);
        elseif ($text == "/help")
            $this->sendMessage([MessageManager::get_help(), MessageManager::get_getAndRepeatKeyboard()]);
        elseif (CommandManager::shouldHandleGetCommand($text))
            $this->handleGetCommand();
        elseif (CommandManager::shouldHandleAgainCommand($text))
            $this->handleAgainCommand();
        else {
            if ($this->creationCache != null)
                $this->createSpeaker($text);
            else if ($this->currentRecordingTextCache != null)
                $this->sendMessage(MessageManager::get_sendOnlyVoiceMessage());
            else
                $this->sendMessage(MessageManager::get_help(), MessageManager::get_getAndRepeatKeyboard());
        }
    }

    function handleGetCommand()
    {
        if ($text = $this->getNextText()) {
            $msg = sprintf(MessageManager::get_textInfo(), $text['text'], $text['description'], $text['expected_time']);
            $this->sendMessage($msg, json_encode(["remove_keyboard" => true]), "HTML");
            $this->storage->write($this->currentRecordingTextCacheKey, $text, []);
        } else
            $this->sendMessage(MessageManager::get_noTextToRecord(), json_encode(["remove_keyboard" => true]));
    }

    function handleAgainCommand()
    {
        if ($this->lastRecordedTextCache == null)
            $this->sendMessage(MessageManager::get_anyTextNotRecordedYet(), MessageManager::get_getKeyboard());
        else if ($this->lastRecordedTextCache['speaker_audio_amount'] == 5)
            $this->sendMessage(MessageManager::get_audioAmountLimitation(), MessageManager::get_getKeyboard());
        else {
            $msg = sprintf(MessageManager::get_textInfo(), $this->lastRecordedTextCache['text'], $this->lastRecordedTextCache['description'], $this->lastRecordedTextCache['expected_time']);

            $this->sendMessage($msg, json_encode(["remove_keyboard" => true]), "HTML");
            $this->storage->write($this->currentRecordingTextCacheKey, $this->lastRecordedTextCache, []);
        }
    }

    function createSpeaker($text)
    {
        if (!isset($this->creationCache['name'])) {
            $this->creationCache['name'] = $text;
            $this->storage->write($this->creationCacheKey, $this->creationCache, []);
            $this->sendMessage(MessageManager::get_askAge());
        } elseif (!isset($this->creationCache['age'])) {
            $age = intval($text);
            if ($age > 6 && $age < 91) {
                $this->creationCache['age'] = $age;
                $this->storage->write($this->creationCacheKey, $this->creationCache, []);
                $this->sendMessage(MessageManager::get_askGender(), MessageManager::get_genderKeyboard());
            } else $this->sendMessage(MessageManager::get_wrongAge());
        } else {
            if ($text == "Мард" || $text == "Зан") {
                $this->creationCache['gender'] = $text;
                $this->storage->remove($this->creationCacheKey);
                $this->addSpeakerToDB($this->creationCache);
                $msg = MessageManager::get_informationFullyEntered() . "\t\n" . MessageManager::get_help();
                $this->sendMessage($msg, MessageManager::get_getKeyboard());
            } else $this->sendMessage(MessageManager::get_wrongGender(), MessageManager::get_genderKeyboard());
        }
    }

    function handleVoiceMessage($voice)
    {
        if ($this->currentRecordingTextCache == null)
            $this->sendMessage(MessageManager::get_firstSelectText(), MessageManager::get_getKeyboard());
        else {
            $fileInfo = $this->api->getFile(['file_id' => $voice['file_id']]);
            $url = "https://api.telegram.org/file/bot" . $this->botToken . "/" . $fileInfo['file_path'];

            $userAudioDir = 'audios/' . $this->userId;
            if (!file_exists($userAudioDir)) mkdir($userAudioDir);

            $audioFileName = $this->currentRecordingTextCache['text_id'] . "_" . time() . ".oga";
            if (file_put_contents($userAudioDir . "/" . $audioFileName, fopen($url, 'r'))) {
                $this->addAudioToDB($this->userId . "/" . $audioFileName);
                $this->storage->remove($this->currentRecordingTextCacheKey);
                $this->currentRecordingTextCache['speaker_audio_amount'] = $this->currentRecordingTextCache['speaker_audio_amount'] + 1;
                $this->storage->write($this->laseRecordedTextCacheKey, $this->currentRecordingTextCache, []);
                $this->sendMessage(MessageManager::get_audioRecorded(), MessageManager::get_getAndRepeatKeyboard());
            } else
                $this->sendMessage(MessageManager::get_cannotDownloadAudioFile(), MessageManager::get_getAndRepeatKeyboard());
        }
    }

    function handleOtherMessages()
    {
        if ($this->creationCache != null)
            $this->sendMessage(MessageManager::get_firstEnterYourInfo());
        else if ($this->currentRecordingTextCache != null)
            $this->sendMessage(MessageManager::get_sendOnlyVoiceMessage());
        else
            $this->sendMessage(MessageManager::get_help(), MessageManager::get_getAndRepeatKeyboard());
    }

    protected function getSpeaker()
    {
        $speakers = mysqli_query($this->database, "SELECT * FROM speakers WHERE id=" . $this->userId);
        $speaker = mysqli_fetch_assoc($speakers);
        mysqli_free_result($speakers);
        return $speaker;
    }

    function getNextText()
    {
        $availableTexts = mysqli_query($this->database,
            "SELECT * FROM `availabe_texts_per_speaker` AS `at` WHERE `at`.`speaker_id` = $this->userId ORDER BY `at`.`priority`, `at`.`speaker_audio_amount`");
        $text = mysqli_fetch_assoc($availableTexts);
        mysqli_free_result($availableTexts);
        return $text;
    }

    function addSpeakerToDB($data)
    {
        if ($this->user == null) {
            $userSql = "INSERT INTO users (`id`,`username`) VALUE(" . $this->userId . ",'" . $this->username . "')";
            mysqli_query($this->database, $userSql);
        }

        $speakerSql = "INSERT INTO speakers (`id`, `name`, `age`, `gender`) VALUE(" .
            $this->userId . ",'" . $data['name'] . "'," . $data['age'] . ",'" . $data['gender'] . "')";
        mysqli_query($this->database, $speakerSql);
    }

    function addAudioToDB($path)
    {
        $audioSql = "INSERT INTO audios (`speaker_id`, `text_id`, `audio_path`)" .
            "VALUE($this->userId," . $this->currentRecordingTextCache['text_id'] . ",'$path')";
        mysqli_query($this->database, $audioSql);
    }
}