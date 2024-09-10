<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum VehicleOwnerTypeEnum: int {
    case INDIVIDUAL = 1;
    case INDIVIDUAL_PERSONAL_USAGE = 2;
    case COMPANY = 3;
}
