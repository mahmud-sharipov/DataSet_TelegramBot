<?php

namespace DataSet\Bot\Speech;

class CommandManager
{
    public static function get_getCommand()
    {
        return (object)[
            'command' => "/get",
            "icon" => "⮚"
        ];
    }

    public static function get_againCommand()
    {
        return (object)[
            'command' => "/again",
            'icon' => "⟲"
        ];
    }

    public static function getCommands()
    {
        return [
            '/start',
            '/help',
            "/again",
            '/get'
        ];
    }

    public static function shouldHandleGetCommand($text): bool
    {
        $get = self::get_getCommand();
        return $text == $get->command || $text == $get->icon;
    }

    public static function shouldHandleAgainCommand($text): bool
    {
        $again = self::get_againCommand();
        return $text == $again->command || $text == $again->icon;
    }
}