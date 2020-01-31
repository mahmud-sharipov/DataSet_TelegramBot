<?php

namespace DataSet\Bot\Admin;

class MessageManager
{
    public static function get_help()
    {
        return "/get - Get new/next audio to validate\n".
                "/approve - approve audio(if the audio matches the text)\n".
                "/reject - reject audio(if the audio doesn't match the text)";
    }

    public static function get_audioFullInfo()
    {
        return '%s (%s)<pre>
Expected time: %s 
Votes: 👍 %s      👎 %s</pre>';
    }

    public static function get_audioShortInfo()
    {
        return '%s (%s)<pre>Expected time: %s</pre>';
    }

    public static function get_noAudioToValidate()
    {
        return "Sorry, there is not audio to validate";
    }

    public static function get_firstGetAnAudioToValidate()
    {
        return "First get an audio to validate. /get";
    }

    /* KEYBOARDS */

    public static function get_getKeyboard()
    {
        return json_encode(
            ['keyboard' => [[CommandManager::get_getCommand()->label]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true]);
    }

    public static function get_validateKeyboard()
    {
        return json_encode([
            'keyboard' => [[CommandManager::get_rejectCommand()->label, CommandManager::get_approveCommand()->label]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true]);
    }

    public static function get_emptyKeyboard()
    {
        return json_encode(["remove_keyboard" => true]);
    }
}