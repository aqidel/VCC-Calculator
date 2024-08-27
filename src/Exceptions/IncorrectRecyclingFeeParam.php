<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator\Exceptions;

use Exception;

class IncorrectRecyclingFeeParam extends Exception
{
    function __construct(string $message)
    {
        parent::__construct($message);
    }
}
