<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
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
     * @param EngineTypeEnum $engineType
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param bool $isCommercialVehicle
     * @return float
     * @throws WrongParamException
     */
    public function calculate(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $enginePower,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
        bool $isCommercialVehicle = false,
    ): float {

        $this->validateInput(
            $enginePower,
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceRUB,
        );

        $customsFee = $this->calculateCustomsFee(
            $vehicleOwnerType,
            $engineType,
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceRUB,
        );

        $recyclingFee = $this->calculateRecyclingFee(
            $vehicleOwnerType,
            $engineType,
            $vehicleAge,
            $engineCapacityKubCm,
            $isCommercialVehicle,
        );

        $clearanceTax = Tariffs::getCustomsClearanceTax($vehiclePriceRUB);
        $exciseDuty = Tariffs::getExciseDuty($this->enginePowerUnitOfMeasurement, $enginePower);
        $vat = $this->calculateVAT($vehiclePriceRUB, $customsFee, $exciseDuty);

        return $customsFee + $recyclingFee + $clearanceTax + $exciseDuty + $vat;
    }

    /**
     * Вычисляем таможенную пошлину
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param EngineTypeEnum $engineType
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @return float
     */
    private function calculateCustomsFee(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
    ): float {
        $vehiclePriceEUR = $vehiclePriceRUB * $this->euroExchangeRate;

        if (
            $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL
            || $vehicleOwnerType === VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE
        ) {
            return Tariffs::getCustomsFeeForIndividual(
                $engineCapacityKubCm,
                $vehicleAge,
                $vehiclePriceEUR
            );
        }

        return Tariffs::getCustomsFeeForCompany(
            $engineType,
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceEUR
        );
    }

    /**
     * Подсчет утильсбора
     * @param VehicleOwnerTypeEnum $vehicleOwnerType
     * @param EngineTypeEnum $engineType
     * @param int $vehicleAge
     * @param int $engineCapacityKubCm
     * @param bool $isCommercialVehicle
     * @return float
     * @throws WrongParamException
     */
    private function calculateRecyclingFee(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        int $vehicleAge,
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

        return $baseRate * (
            $isOwnerAnIndividual
                ? Tariffs::getRecyclingFeeCoefficientForIndividual($engineType, $engineCapacityKubCm, $vehicleAge)
                : Tariffs::getRecyclingFeeCoefficientForCompany($engineType, $engineCapacityKubCm, $vehicleAge)
            );
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
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @return void
     * @throws WrongParamException
     */
    private function validateInput(
        int $enginePower,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
    ): void {
        if ($enginePower <= 0) {
            throw new WrongParamException('Engine power can\'t be less than or equal to 0!');
        }

        if ($engineCapacityKubCm <= 0) {
            throw new WrongParamException('Engine capacity can\'t be less than or equal to 0!');
        }

        if ($vehicleAge < 0) {
            throw new WrongParamException('Vehicle age can\'t be less than 0!');
        }

        if ($vehiclePriceRUB <= 0) {
            throw new WrongParamException('Vehicle price can\'t be less than or equal to 0!');
        }

        if (!isset($this->euroExchangeRate) || $this->euroExchangeRate <= 0) {
            throw new WrongParamException('Euro exchange rate isn\'t set or set to incorrect rate!');
        }
    }
}
