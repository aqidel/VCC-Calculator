<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum EngineTypeEnum: int {
    case GASOLINE = 1;
    case DIESEL = 2;
    case ELECTRIC = 3;
    case HYBRID = 4;
}
