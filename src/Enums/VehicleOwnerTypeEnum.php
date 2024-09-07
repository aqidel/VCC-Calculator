<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum VehicleOwnerTypeEnum: int {
    case PERSON = 1;
    case PERSON_RESALE = 2;
    case COMPANY = 3;
}
