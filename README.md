# Калькулятор таможенных платежей на ввоз автомобиля

<a href="https://packagist.org/packages/aqidel/vcc-calculator">
    <img src="https://img.shields.io/packagist/v/aqidel/vcc-calculator" alt="Latest Stable Version">
</a>

<a href="https://packagist.org/packages/aqidel/vcc-calculator">
    <img src="https://img.shields.io/packagist/dt/aqidel/vcc-calculator" alt="Total Downloads">
</a>

Производит расчет таможенного платежа за ввоз легкового автомобиля на территорию РФ.

> [!WARNING]
> Правильность подсчета платежа не гарантируется! Если Вы сомневаетесь в корректности данных, посоветуйтесь с бухгалтером.
> Ставки тарифов актуальны на сентябрь 2024 года.

## Требования

- PHP 8.1 или выше

## Установка

```bash
composer require aqidel/vcc-calculator
```

## Использование

```php
use Aqidel\VCCCalculator\Enums\EnginePowerUnitOfMeasurementEnum;
use Aqidel\VCCCalculator\Enums\EngineTypeEnum;
use Aqidel\VCCCalculator\Enums\VehicleAgeEnum;
use Aqidel\VCCCalculator\Enums\VehicleOwnerTypeEnum;
use Aqidel\VCCCalculator\VCCCalculator;

// Устанавливаем единицу измерения мощности двигателя (Л.С./кВт)
// и курс евро к рублю
$calculator = new VCCCalculator(
    EnginePowerUnitOfMeasurementEnum::HORSEPOWER,
    103.3773,
);

// Передаем следующие параметры:
// Тип владельца (физ. лицо / физ. лицо для личного пользования / юр. лицо
// Возраст авто (менее 3 лет / от 3 до 5 лет / от 5 до 7 лет / более 7 лет
// Тип двигателя (бензиновый / дизельный / электрический / гибрид)
// Мощность двигателя
// Объем двигателя (в куб. см.)
// Стоимость авто (в рублях)
$result = $this->calculator->calculate(
    VehicleOwnerTypeEnum::INDIVIDUAL_PERSONAL_USAGE,
    VehicleAgeEnum::LESS_THAN_3,
    EngineTypeEnum::GASOLINE,
    120,
    2000,
    700000.00,
);
```

## Поддержка

Проект поддерживается [Aqidel](https://github.com/aqidel).

## Лицензия

Открытая лицензия MIT.