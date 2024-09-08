<?php

declare(strict_types=1);

namespace Aqidel\VCCCalculator;

use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\Exceptions\IncorrectExciseDutyParamException;
use Aqidel\VCCCalculator\Exceptions\IncorrectRecyclingFeeParamException;
use Aqidel\VCCCalculator\Exceptions\IncorrectVehiclePriceException;
use Exception;

final class VCCCalculator
{
    private VehicleOwnerTypeEnum $vehicleOwnerType;
    private EngineTypeEnum $engineType;
    private EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement;
    private float $euroExchangeRate;

    public function __construct(
        VehicleOwnerTypeEnum $vehicleOwnerType,
        EngineTypeEnum $engineType,
        EnginePowerUnitOfMeasurementEnum $enginePowerUnitOfMeasurement,
        float $euroExchangeRate,
    ) {
        $this->vehicleOwnerType = $vehicleOwnerType;
        $this->engineType = $engineType;
        $this->enginePowerUnitOfMeasurement = $enginePowerUnitOfMeasurement;
        $this->euroExchangeRate = $euroExchangeRate;
    }

    public function setVehicleOwnerType(VehicleOwnerTypeEnum $vehicleOwnerType): self
    {
        $this->vehicleOwnerType = $vehicleOwnerType;

        return $this;
    }

    public function getVehicleOwnerType(): VehicleOwnerTypeEnum
    {
        return $this->vehicleOwnerType;
    }

    public function setEngineType(EngineTypeEnum $engineType): self
    {
        $this->engineType = $engineType;

        return $this;
    }

    public function getEngineType(): EngineTypeEnum
    {
        return $this->engineType;
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
     * @param int $enginePower
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @param bool $isForPersonalUsage
     * @param bool $isCommercialVehicle
     * @return float
     * @throws IncorrectExciseDutyParamException
     * @throws IncorrectVehiclePriceException
     * @throws IncorrectRecyclingFeeParamException
     */
    public function calculate(
        int $enginePower,
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
        bool $isForPersonalUsage = false,
        bool $isCommercialVehicle = false,
    ): float {
        $customsFee = $this->calculateCustomsFee(
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceRUB,
        );

        $recyclingFee = $this->calculateRecyclingFee(
            $vehicleAge,
            $engineCapacityKubCm,
            $isForPersonalUsage,
            $isCommercialVehicle,
        );

        $clearanceTax = Tariffs::getCustomsClearanceTax($vehiclePriceRUB);
        $exciseDuty = Tariffs::getExciseDuty($this->enginePowerUnitOfMeasurement, $enginePower);

        return $customsFee + $recyclingFee + $clearanceTax + $exciseDuty;
    }

    /**
     * Вычисляем таможенную пошлину
     * @param int $engineCapacityKubCm
     * @param int $vehicleAge
     * @param float $vehiclePriceRUB
     * @return float
     */
    private function calculateCustomsFee(
        int $engineCapacityKubCm,
        int $vehicleAge,
        float $vehiclePriceRUB,
    ): float {
        $vehiclePriceEUR = $vehiclePriceRUB * $this->euroExchangeRate;

        if ($this->vehicleOwnerType === VehicleOwnerTypeEnum::PERSON) {
            return Tariffs::getCustomsFeeForIndividual(
                $engineCapacityKubCm,
                $vehicleAge,
                $vehiclePriceEUR
            );
        }

        return Tariffs::getCustomsFeeForCompany(
            $this->engineType,
            $engineCapacityKubCm,
            $vehicleAge,
            $vehiclePriceEUR
        );
    }

    /**
     * Подсчет утильсбора
     * @param int $vehicleAge
     * @param int $engineCapacityKubCm
     * @param bool $isForPersonalUsage
     * @param bool $isCommercialVehicle
     * @return float
     * @throws IncorrectRecyclingFeeParamException
     */
    private function calculateRecyclingFee(
        int $vehicleAge,
        int $engineCapacityKubCm,
        bool $isForPersonalUsage = false,
        bool $isCommercialVehicle = false,
    ): float {
        if ($isForPersonalUsage && $engineCapacityKubCm < Tariffs::ENGINE_CAPACITY_EXEMPTION) {
            return Tariffs::getRecyclingFeeForPersonalUsage($vehicleAge);
        }

        $baseRate = Tariffs::getRecyclingFeeBaseRate($isCommercialVehicle);

        return $baseRate * ($this->vehicleOwnerType === VehicleOwnerTypeEnum::PERSON
                ? Tariffs::getRecyclingFeeCoefficientForPerson($this->engineType, $engineCapacityKubCm, $vehicleAge)
                : Tariffs::getRecyclingFeeCoefficientForCompany($this->engineType, $engineCapacityKubCm, $vehicleAge)
            );
    }
}
