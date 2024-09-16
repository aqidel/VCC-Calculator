<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Enums;

enum EngineTypeEnum: string
{
    case GASOLINE = 'GASOLINE';
    case DIESEL = 'DIESEL';
    case ELECTRIC = 'ELECTRIC';
    case HYBRID = 'HYBRID';
}
