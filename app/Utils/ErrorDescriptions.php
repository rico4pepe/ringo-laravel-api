<?php

namespace App\Utils;


class ErrorDescriptions
{
    public static function lookup($code)
    {
        $map = [
            "000" => "Delivered",
            "0dc" => "Absent Subscriber",
            206 => "Absent Subscriber",
            "21b" => "Absent Subscriber",
            "023" => "Absent Subscriber",
            "027" => "Absent Subscriber",
            "053" => "Absent Subscriber",
            "054" => "Absent Subscriber",
            "058" => "Absent Subscriber",
            439 => "Absent subscriber or ported subscriber or subscriber is barred",
            254 => "Subscriber's phone inbox is full",
            220 => "Subscriber's phone inbox is full",
            120 => "Subscriber's phone inbox is full",
            "008" => "Subscriber's phone inbox is full",
            255 => "Invalid or inactive mobile number or subscriber's phone inbox is full",
            0 => "Invalid or inactive mobile number or subscriber's phone inbox is full",
            "20b" => "Invalid or inactive mobile number",
            "004" => "Invalid or inactive mobile number",
            510 => "Invalid or inactive mobile number",
            215 => "Invalid or inactive mobile number",
            "20d" => "Subscriber is barred on the network",
            130 => "Subscriber is barred on the network",
            131 => "Subscriber is barred on the network",
            222 => "Network operator system failure",
            602 => "Network operator system failure",
            306 => "Network operator system failure",
            "032" => "Network operator system failure or operator not supported",
            "085" => "Subscriber is on DND",
            "065" => "Message content or senderID is blocked on the promotional route",
            600 => "Message content or senderID is blocked on the promotional route",
            "40a" => "SenderID not whitelisted on the account",
            "082" => "Network operator not supported",
            "00a" => "SenderID is restricted by the operator",
            "078" => "Restricted message content or senderID is blocked.",
            432 => "Restricted message content or senderID is blocked.",
        ];

        return $map[$code] ?? 'Unknown Error';
    }
}
