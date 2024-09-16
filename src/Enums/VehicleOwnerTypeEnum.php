<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum VehicleOwnerTypeEnum: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case INDIVIDUAL_PERSONAL_USAGE = 'PERSONAL_USAGE';
    case COMPANY = 'COMPANY';
}
