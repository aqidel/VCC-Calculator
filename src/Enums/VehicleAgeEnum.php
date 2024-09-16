<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum VehicleAgeEnum: string
{
    case LESS_THAN_3 = '0-3';
    case FROM_3_TO_5 = '3-5';
    case FROM_5_TO_7 = '5-7';
    case MORE_THAN_7 = '7--';
}
