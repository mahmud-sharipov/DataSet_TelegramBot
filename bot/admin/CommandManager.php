<?php

namespace DataSet\Bot\Admin;

class CommandManager
{
    public static function get_getCommand()
    {
        return (object)['command' => "/get", "label" => "Get audio"];
    }

    public static function get_approveCommand()
    {
        return (object)['command' => "/approve", "label" => "Approve"];
    }

    public static function get_rejectCommand()
    {
        return (object)['command' => "/reject", "label" => "Reject"];
    }

    public static function get_validationCommands()
    {
        return ["/approve", "Approve", "/reject", "Reject"];
    }

    public static function shouldHandleGetCommand($text): bool
    {
        $get = self::get_getCommand();
        return $text == $get->command || $text == $get->label;
    }

    public static function shouldHandleApproveCommand($text): bool
    {
        $approve = self::get_approveCommand();
        return $text == $approve->command || $text == $approve->label;
    }
}