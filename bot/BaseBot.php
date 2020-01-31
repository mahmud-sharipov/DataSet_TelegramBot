<?php
namespace DataSet\Bot;

use Telegram\Bot\Api;

abstract class BaseBot
{
    protected $api;
    protected $servername = "localhost";
    protected $db_username = "id12318158_botadmin";
    protected $password = "admin";
    protected $db_name = "id12318158_dataset";
    protected $update;
    protected $message;
    protected $chatId;
    protected $username;
    protected $userId;
    protected $storage;
    protected $database;
    protected $user;
    protected $botToken;

    public function __construct($apiToken){
        $this->botToken = $apiToken;
        $this->api = new Api($apiToken);
        $this->update = $this->api->getWebhookUpdates();
        $this->message = $this->update['message'];
        $this->chatId = $this->message['chat']['id'];
        $this->username = $this->message['from']['username'];
        $this->userId = $this->message['from']['id'];
        $this->database = mysqli_connect($this->servername, $this->db_username, $this->password, $this->db_name);
        mysqli_query($this->database, "SET NAMES utf8");
        $this->user = $this->getUser();
    }

    abstract public function handle();

    protected function getUser(){
        $users = mysqli_query($this->database, "SELECT * FROM users WHERE id=" . $this->userId);
        $user = mysqli_fetch_assoc($users);
        mysqli_free_result($users);
        return $user;
    }

    protected function sendMessage($text, $keyboard = null, $parseMode = null){
        $params = [ 'chat_id' => $this->chatId, 'text' => $text];
        if($keyboard) $params['reply_markup'] = $keyboard;
        if($parseMode) $params['parse_mode'] = $parseMode;

        $this->api->sendMessage($params);
    }

    protected function sendVoice($audio, $keyboard = null)
    {
        $params = ['chat_id' => $this->chatId, 'voice' => $audio];
        if ($keyboard) $params['reply_markup'] = $keyboard;

        $this->api->sendVoice($params);
    }

    public function dispose()
    {
        mysqli_close($this->database);
    }

}