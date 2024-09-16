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
     * @param bool $isCommercialVehicle
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
        bool $isCommercialVehicle = false,
    ): float {
        $this->validateInput(
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
            $isCommercialVehicle,
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
            return $customsFee + $recyclingFee + $clearanceTax;
        }

        $exciseDuty = Tariffs::getExciseDuty($this->enginePowerUnitOfMeasurement, $enginePower);
        $vat = $this->calculateVAT($vehiclePriceRUB, $customsFee, $exciseDuty);

        return $customsFee + $recyclingFee + $clearanceTax + $exciseDuty + $vat;
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

        return Tariffs::getCustomsFeeForCompany(
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
     * @param bool $isCommercialVehicle
     * @return float
     */
    private function calculateRecyclingFee(
        VehicleAgeEnum $vehicleAge,
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $engineCapacityKubCm,
        bool $isCommercialVehicle = false,
    ): float {
        if (
            $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE
            && $engineCapacityKubCm < Tariffs::ENGINE_CAPACITY_EXEMPTION
        ) {
            return Tariffs::getRecyclingFeeForPersonalUsage($vehicleAge);
        }

        $baseRate = Tariffs::getRecyclingFeeBaseRate($isCommercialVehicle);
        $isOwnerAnIndividual = $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL
            || $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE;

        $coefficient = $isOwnerAnIndividual
            ? Tariffs::getRecyclingFeeCoefficientForIndividual($vehicleAge, $engineType, $engineCapacityKubCm)
            : Tariffs::getRecyclingFeeCoefficientForCompany($vehicleAge, $engineType, $engineCapacityKubCm);

        return round($baseRate * $coefficient, 2);
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
        return round(($vehiclePriceRUB + $customsFee + $exciseDuty) * Tariffs::BASE_VAT, 2);
    }

    /**
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param float $vehiclePriceRUB
     * @return void
     * @throws WrongParamException
     */
    private function validateInput(
        int $enginePower,
        int $engineCapacityKubCm,
        float $vehiclePriceRUB,
    ): void {
        if ($enginePower <= 0) {
            throw new WrongParamException('Engine power can\'t be less than or equal to 0!');
        }

        if ($engineCapacityKubCm <= 0) {
            throw new WrongParamException('Engine capacity can\'t be less than or equal to 0!');
        }

        if ($vehiclePriceRUB <= 0) {
            throw new WrongParamException('Vehicle price can\'t be less than or equal to 0!');
        }

        if (!isset($this->euroExchangeRate) || $this->euroExchangeRate <= 0) {
            throw new WrongParamException('Euro exchange rate isn\'t set or set to incorrect rate!');
        }
    }
}
