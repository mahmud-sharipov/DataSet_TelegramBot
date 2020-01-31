<?php

namespace DataSet\Bot\Admin;

use DataSet\Bot\BaseBot;
use Exception;
use Nette\Caching\Storages\FileStorage;

class AdminBot extends BaseBot
{

    protected $currentAudioCacheKey;

    public function __construct($apiToken)
    {
        parent::__construct($apiToken);
        $this->storage = new FileStorage('temp/admin');
        $this->currentAudioCacheKey = $this->userId . ".currentAudio";
        if ($this->user == null)
            $this->createUser();
    }

    public function handle()
    {
        try {
            if ($text = $this->message['text'])
                $this->handleTextMessage($text);
            else
                $this->sendMessage(MessageManager::get_help(), MessageManager::get_getKeyboard());
        } catch (Exception $exception) {
            $this->sendMessage($exception->getMessage());
        } finally {
            $this->dispose();
        }
    }

    function handleTextMessage($text)
    {
        if ($text == "/start" || $text == "/help")
            $this->sendMessage(MessageManager::get_help(), MessageManager::get_getKeyboard());
        elseif (CommandManager::shouldHandleGetCommand($text))
            $this->handleGetCommand();
        else if (in_array($text, CommandManager::get_validationCommands()))
            $this->handleValidation($text);
        else
            $this->sendMessage(MessageManager::get_help(), MessageManager::get_getKeyboard());
    }

    function handleGetCommand()
    {
        if ($audio = $this->getNextAudio()) {
            $this->sendVoice('http://my-telgram-bot.000webhostapp.com/audios/' . $audio['audio_path']);

            $msg = null;
            if ($this->user['is_admin'] == 1)
                $msg = sprintf(MessageManager::get_audioFullInfo(),
                    $audio['text'],
                    $audio['description'],
                    $audio['expected_time'],
                    $audio['approved_amount'],
                    $audio['rejected_amount']
                );
            else
                $msg = sprintf(MessageManager::get_audioShortInfo(),
                    $audio['text'],
                    $audio['description'],
                    $audio['expected_time']
                );;

            $this->sendMessage($msg, MessageManager::get_validateKeyboard(), 'HTML');
            $this->storage->write($this->currentAudioCacheKey, $audio, []);
        } else
            $this->sendMessage(MessageManager::get_noAudioToValidate(), MessageManager::get_emptyKeyboard());
    }

    function getNextAudio()
    {
        $sql = null;
        if ($this->user['is_admin'] == '1') {
            $sql = "SELECT a.*, t.`text`, t.`classification`, t.`description`, t.`priority`, t.`expected_time`, \n" .
                "(a.`rejected_amount` + a.`approved_amount`) AS total_votes \n" .
                "FROM `audios` a, `texts` t \n" .
                "WHERE a.`text_id` = t.`id` AND a.is_validated = 0 \n" .
                "ORDER BY t.`priority`, total_votes DESC LIMIT 1";
        } else {
             $sql = "SELECT a.*, t.`text`, t.`classification`, t.`description`, t.`priority`, t.`expected_time`, \n" .
                "(a.`rejected_amount` + a.`approved_amount`) AS total_votes \n" .
                "FROM `audios` a, `texts` t \n" .
                "WHERE a.`text_id` = t.`id` AND a.is_validated = 0 AND a.`speaker_id` != ".$this->userId." AND \n" .
                "(SELECT COUNT(av.`audio_id`) FROM audio_votes av WHERE av.`audio_id`=a.`id` AND av.`user_id` = ".$this->userId.") = 0 \n" .
                "ORDER BY t.`priority`, total_votes LIMIT 1";
        }

        $audios = mysqli_query($this->database, $sql);
        $audio = mysqli_fetch_assoc($audios);
        mysqli_free_result($audios);
        return $audio;
    }

    function handleValidation($text)
    {
        if ($currentAudio = $this->storage->read($this->currentAudioCacheKey)) {
            if ($this->user['is_admin'] == 1)
                $this->validate($currentAudio, CommandManager::shouldHandleApproveCommand($text));
            else
                $this->addVote($currentAudio, CommandManager::shouldHandleApproveCommand($text));
            $this->handleGetCommand();
        } else
            $this->sendMessage(MessageManager::get_firstGetAnAudioToValidate(), MessageManager::get_getKeyboard());
    }

    function validate($currentAudio, $isValid)
    {
        if ($isValid) {
            mysqli_query($this->database, "UPDATE `audios` SET `is_validated` = 1 WHERE id = " . $currentAudio["id"]);
            if (!file_exists('audios/approved/' . $currentAudio['speaker_id']))
                mkdir('audios/approved/' . $currentAudio['speaker_id']);
            rename("audios/" . $currentAudio['audio_path'], "audios/approved/" . $currentAudio['audio_path']);
        } else {
            mysqli_query($this->database, "DELETE FROM `audios` WHERE id = " . $currentAudio["id"]);
            unlink("audios/" . $currentAudio['audio_path']);
        }
    }

    function addVote($currentAudio, $isValid)
    {
        $vote = $isValid ? 1 : 0;
        $audioId = $currentAudio['id'];
        mysqli_query($this->database, "INSERT INTO `audio_votes` (`user_id`,`audio_id`,`is_correct`) VALUE ".
            "($this->userId,$audioId,$vote)");
    }

    function createUser()
    {
        $userSql = "INSERT INTO users (`id`,`username`) VALUE(" . $this->userId . ",'" . $this->username . "')";
        $this->user = [
            'id' => $this->userId,
            'username' => $this->username,
            'is_admin' => 0
        ];
        mysqli_query($this->database, $userSql);
    }
}