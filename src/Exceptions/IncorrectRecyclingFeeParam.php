<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Exceptions;

use Aqidel\VCCCalculator\Enums\IncorrectRecyclingFeeParamEnum;
use Exception;

class IncorrectRecyclingFeeParam extends Exception
{
    function __construct(IncorrectRecyclingFeeParamEnum $case)
    {
        match ($case) {
            IncorrectRecyclingFeeParamEnum::IncorrectEngineCapacity => parent::__construct('Incorrect engine capacity!'),
            IncorrectRecyclingFeeParamEnum::IncorrectVehicleAge => parent::__construct('Incorrect vehicle age!'),
        };
    }
}
