<?php

namespace App\Search;

class Provinces
{
    public static function getProvinceList(): array
    {
        return [
            'Alberta',
            'British Columbia',
            'Manitoba',
            'New Brunswick',
            'Newfoundland and Labrador',
            'Northwest Territories',
            'Nova Scotia',
            'Nunavut',
            'Ontario',
            'Prince Edward Island',
            'Quebec',
            'Saskatchewan',
            'Yukon'
        ];
    }

    public static function getProvincialAbbreviationList(): array
    {
        return [
            'AB',
            'BC',
            'MB',
            'NB',
            'NL',
            'NT',
            'NS',
            'NU',
            'ON',
            'PE',
            'QC',
            'SK',
            'YT'
        ];
    }

    public static function formatProvinceName(string $province): string
    {
        if (strcasecmp($province, ' BC ') === 0) {
            return 'British Columbia';
        }
        if (strcasecmp($province, ' PEI ') === 0) {
            return 'Prince Edward Island';
        }
        if (strcasecmp($province, 'Newfoundland & Labrador') === 0
            || strcasecmp($province, 'Newfoundland') === 0) {
            return 'Newfoundland and Labrador';
        }
        return $province;
    }

    public static function getNameFromAbbreviation(string $abbreviation): string
    {
        $provinceNames = [
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NT' => 'Northwest Territories',
            'NS' => 'Nova Scotia',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon'
        ];
        return $provinceNames[$abbreviation];
    }

    public static function getAbbreviationFromName(string $provinceName): string
    {
        $provinceName = strtolower($provinceName);
        $provinceAbbreviations = [
            'alberta' => 'AB',
            'british columbia' => 'BC',
            'manitoba' => 'MB',
            'new brunswick' => 'NB',
            'newfoundland and labrador' => 'NL',
            'northwest territories' => 'NT',
            'nova scotia' => 'NS',
            'nunavut' => 'NU',
            'ontario' => 'ON',
            'prince edward island' => 'PE',
            'quebec' => 'QC',
            'saskatchewan' => 'SK',
            'yukon' => 'YT'
        ];
        return $provinceAbbreviations[$provinceName];
    }
}
