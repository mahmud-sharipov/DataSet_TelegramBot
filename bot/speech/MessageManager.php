<?php

namespace DataSet\Bot\Speech;

class MessageManager
{
    public static function get_startNewUser()
    {
        return "Салом, \t\nТашакур барои ки мехоҳед саҳми худро дар ин лоиҳа гузоред." .
            " Хоҳишмандам ба саволҳои зерин посух диҳед: \t\nНоми шумо?";
    }

    public static function get_askAge()
    {
        return "Сину сол?";
    }

    public static function get_wrongAge()
    {
        return "Сину соли шумо бояд адат бутун ва дар фосилаи 7-90 бошад!";
    }

    public static function get_askGender()
    {
        return "Ҷинс? (Мард, Зан)";
    }

    public static function get_wrongGender()
    {
        return "Ҷинс бояд ки 'Мард' ё 'Зан' бошвд";
    }

    public static function get_informationFullyEntered()
    {
        return "Ташакур!";
    }

    public static function get_firstEnterYourInfo()
    {
        return "Лутфат аввал ба саволҳо ҷавоб медодед! \t\nИн маълумотҳо хело зарур мебошад.";
    }

    public static function get_sendOnlyVoiceMessage()
    {
        return "Танҳо сабти овозро ирсол кунед";
    }

    public static function get_firstSelectText()
    {
        return "Аввал матнро дархост кунед!";
    }

    public static function get_audioRecorded()
    {
        return "Ташакур, (матни навбати - " . CommandManager::get_getCommand()->command .
            " , такроран мабт кардан -} " . CommandManager::get_againCommand()->command;
    }

    public static function get_help()
    {
        return "/get-Манти навбати  \t\n/again-Такроран сабт кардани овоз(танҳо дар ҳолате ки Шумо барои ягон мант овоз сабт кардаед!)";
    }

    public static function get_textInfo()
    {
        return "<b>%s</b> (%s)\n\n<pre>Давомнокии сабт: <b>%s</b> сония</pre>";
    }

    public static function get_anyTextNotRecordedYet()
    {
        return "Шумо ҳоло барои ягон мант овоз сабт накардаед. " . CommandManager::get_getCommand()->comman . " - матни} навбати";
    }

    public static function get_noTextToRecord()
    {
        return "Мутфссифона дигар матн барои сабт мавҷуд нест. Лутфан дархости худро дертар такрор кунед. \t\nТАШАКУР!";
    }

    public static function get_audioAmountLimitation()
    {
        return "Шумо наметавонед барои як мант зиёда аз 5 маротиба овоз сабт кунед. Лутфан дигар матнро интихоб кунед!";
    }

    public static function get_cannotDownloadAudioFile()
    {
        return "Хатогӣ ҳангоми сабти маълумот. Лутфан бори дигар такрор кунед. " .
            "\t\nАгар ин} хабар такрор шуда бошад  ба @shamah_mahmud муроҷиат кунед.";
    }

    /* KEYBOARDS */
    public static function get_getAndRepeatKeyboard()
    {
        return json_encode(['keyboard' => [[CommandManager::get_againCommand()->icon, CommandManager::get_getCommand()->icon]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true]);
    }

    public static function get_getKeyboard()
    {
        return json_encode(['keyboard' => [[CommandManager::get_getCommand()->icon]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true]);
    }

    public static function get_genderKeyboard()
    {
        return json_encode(['keyboard' => [["Мард", "Зан"]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true]);
    }
}