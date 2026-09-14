<?php

declare(strict_types = 1);

namespace App\Enums;

enum Uf: string
{
    case AC = 'AC';
    case AL = 'AL';
    case AP = 'AP';
    case AM = 'AM';
    case BA = 'BA';
    case CE = 'CE';
    case DF = 'DF';
    case ES = 'ES';
    case GO = 'GO';
    case MA = 'MA';
    case MT = 'MT';
    case MS = 'MS';
    case MG = 'MG';
    case PA = 'PA';
    case PB = 'PB';
    case PR = 'PR';
    case PE = 'PE';
    case PI = 'PI';
    case RJ = 'RJ';
    case RN = 'RN';
    case RO = 'RO';
    case RR = 'RR';
    case RS = 'RS';
    case SC = 'SC';
    case SE = 'SE';
    case SP = 'SP';
    case TO = 'TO';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

}