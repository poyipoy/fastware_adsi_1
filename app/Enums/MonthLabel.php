<?php

namespace App\Enums;

enum MonthLabel: string
{
    case January = 'Jan';
    case February = 'Feb';
    case March = 'Mar';
    case April = 'Apr';
    case May = 'Mei';
    case June = 'Jun';
    case July = 'Jul';
    case August = 'Ags';
    case September = 'Sep';
    case October = 'Okt';
    case November = 'Nov';
    case December = 'Des';

    public static function labels(): array
    {
        return array_map(
            static fn (self $label): string => $label->value,
            self::cases()
        );
    }
}

