<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\Exceptions\WrongParamException;

final class VCCCalculator
{
    /**
     * Единица измерения мощности двигателя
     * @var EnginePowerUnitOfMeasurementEnum
     */
    private EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement;
    private float $euroExchangeRate;

    public function __construct(
        EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement,
        float $euroExchangeRate,
    ) {
        $this->enginePowerUnitOfMeasurement = $enginePowerUnitOfMeasurement;
        $this->euroExchangeRate = $euroExchangeRate;
    }

    public function setEnginePowerUnitOfMeasurement(EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement): self
    {
        $this->enginePowerUnitOfMeasurement = $enginePowerUnitOfMeasurement;

        return $this;
    }

    public function getEnginePowerUnitOfMeasurement(): EnginePowerUnitOfMeasurementEnum
    {
        return $this->enginePowerUnitOfMeasurement;
    }

    public function setEuroExchangeRate(float $euroExchangeRate): self
    {
        $this->euroExchangeRate = $euroExchangeRate;

        return $this;
    }

    public function getEuroExchangeRate(): float
    {
        return $this->euroExchangeRate;
    }

    /**
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param float $vehiclePriceRUB
     * @return float
     * @throws WrongParamException
     */
    public function calculate(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $enginePower,
        int $engineCapacityKubCm,
        float $vehiclePriceRUB,
    ): float {
        $this->validateInput(
            $engineType,
            $enginePower,
            $engineCapacityKubCm,
            $vehiclePriceRUB,
        );

        $customsFee = $this->calculateCustomsFee(
            $vehicleOwnerType,
            $vehicleAge,
            $engineType,
            $engineCapacityKubCm,
            $vehiclePriceRUB,
        );

        $recyclingFee = $this->calculateRecyclingFee(
            $vehicleAge,
            $vehicleOwnerType,
            $engineType,
            $engineCapacityKubCm,
        );

        $clearanceTax = Tariffs::getCustomsClearanceTax($vehiclePriceRUB);

        // Физ. лица не платят акциз и НДС, кроме как на электромобили
        if (
            (
                $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE
                || $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL
            )
            && $engineType !== EngineTypeEnum::ELECTRIC
        ) {
            return round($customsFee + $recyclingFee + $clearanceTax, 2);
        }

        $exciseDuty = Tariffs::getExciseDuty($this->enginePowerUnitOfMeasurement, $enginePower);
        $vat = $this->calculateVAT($vehiclePriceRUB, $customsFee, $exciseDuty);

        return round($customsFee + $recyclingFee + $clearanceTax + $exciseDuty + $vat, 2);
    }

    /**
     * Вычисляем таможенную пошлину
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param VehicleAgeEnum $vehicleAge
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacityKubCm
     * @param float $vehiclePriceRUB
     * @return float
     */
    private function calculateCustomsFee(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        VehicleAgeEnum $vehicleAge,
        EngineTypeEnum $engineType,
        int $engineCapacityKubCm,
        float $vehiclePriceRUB,
    ): float {
        if (
            $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL
            || $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE
        ) {
            return Tariffs::getCustomsFeeForIndividual(
                $vehicleAge,
                $engineType,
                $engineCapacityKubCm,
                $vehiclePriceRUB,
                $this->euroExchangeRate,
            );
        }

        return Tariffs::getCustomsFeeForCompanyByEngineType(
            $vehicleAge,
            $engineType,
            $engineCapacityKubCm,
            $vehiclePriceRUB,
            $this->euroExchangeRate,
        );
    }

    /**
     * Подсчет утильсбора
     * @param VehicleAgeEnum $vehicleAge
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacityKubCm
     * @return float
     */
    private function calculateRecyclingFee(
        VehicleAgeEnum $vehicleAge,
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $engineCapacityKubCm,
    ): float {
        $coefficient = match ($vehicleOwnerType) {
            VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE => Tariffs::getRecyclingFeeCoefficientForPersonalUsage(
                $vehicleAge,
                $engineType,
                $engineCapacityKubCm
            ),
            VehicleOwnerTypeEnum::INDIVIDUAL => Tariffs::getRecyclingFeeCoefficientForIndividual(
                $vehicleAge,
                $engineType,
                $engineCapacityKubCm
            ),
            VehicleOwnerTypeEnum::COMPANY => Tariffs::getRecyclingFeeCoefficientForCompany(
                $vehicleAge,
                $engineType,
                $engineCapacityKubCm
            ),
        };

        return Tariffs::RECYCLING_FEE_BASE_RATE * $coefficient;
    }

    /**
     * Расчет НДС
     * @param float $vehiclePriceRUB
     * @param float $customsFee
     * @param int $exciseDuty
     * @return float
     */
    private function calculateVAT(
        float $vehiclePriceRUB,
        float $customsFee,
        int $exciseDuty,
    ): float {
        return ($vehiclePriceRUB + $customsFee + $exciseDuty) * Tariffs::BASE_VAT;
    }

    /**
     * @param EngineTypeEnum $engineType
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param float $vehiclePriceRUB
     * @return void
     * @throws WrongParamException
     */
    private function validateInput(
        EngineTypeEnum $engineType,
        int $enginePower,
        int $engineCapacityKubCm,
        float $vehiclePriceRUB,
    ): void {
        if ($enginePower <= 0) {
            throw new WrongParamException('Engine power can\'t be less than or equal to 0!');
        }

        if ($engineCapacityKubCm <= 0 && $engineType !== EngineTypeEnum::ELECTRIC) {
            throw new WrongParamException('Engine capacity can\'t be less than or equal to 0!');
        }

        if ($engineCapacityKubCm !== 0 && $engineType === EngineTypeEnum::ELECTRIC) {
            throw new WrongParamException('Electric vehicle can\'t have engine capacity!');
        }

        if ($vehiclePriceRUB <= 0) {
            throw new WrongParamException('Vehicle price can\'t be less than or equal to 0!');
        }

        if (!isset($this->euroExchangeRate) || $this->euroExchangeRate <= 0) {
            throw new WrongParamException('Euro exchange rate isn\'t set or set to incorrect rate!');
        }
    }
}
