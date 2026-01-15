<?php

namespace Database\Seeders;

use App\Models\CountryTravelAdvisory;
use Illuminate\Database\Seeder;

class CountryTravelAdvisorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Level 4 - Do Not Travel
        $level4 = [
            'AF' => 'Afghanistan',
            'BY' => 'Belarus',
            'MM' => 'Myanmar',
            'CF' => 'Central African Republic',
            'ET' => 'Ethiopia',
            'HT' => 'Haiti',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'KP' => 'North Korea',
            'LB' => 'Lebanon',
            'LY' => 'Libya',
            'ML' => 'Mali',
            'NI' => 'Nicaragua',
            'RU' => 'Russia',
            'SO' => 'Somalia',
            'SS' => 'South Sudan',
            'SD' => 'Sudan',
            'SY' => 'Syria',
            'UA' => 'Ukraine',
            'VE' => 'Venezuela',
            'YE' => 'Yemen',
        ];

        // Prohibited per POPVOX policy
        $prohibited = ['RU', 'CN', 'IR', 'KP'];

        // Level 3 - Reconsider Travel
        $level3 = [
            'DZ' => 'Algeria',
            'BD' => 'Bangladesh',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CM' => 'Cameroon',
            'TD' => 'Chad',
            'CO' => 'Colombia',
            'CD' => 'Democratic Republic of the Congo',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'HN' => 'Honduras',
            'MR' => 'Mauritania',
            'MZ' => 'Mozambique',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'PK' => 'Pakistan',
            'PH' => 'Philippines',
            'SN' => 'Senegal',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'UG' => 'Uganda',
            'CN' => 'China',
            'CU' => 'Cuba',
        ];

        // Level 2 - Exercise Increased Caution
        $level2 = [
            'AR' => 'Argentina',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BZ' => 'Belize',
            'BA' => 'Bosnia and Herzegovina',
            'BR' => 'Brazil',
            'CL' => 'Chile',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GE' => 'Georgia',
            'GT' => 'Guatemala',
            'GY' => 'Guyana',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IL' => 'Israel',
            'JM' => 'Jamaica',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'MY' => 'Malaysia',
            'MX' => 'Mexico',
            'MA' => 'Morocco',
            'NP' => 'Nepal',
            'PA' => 'Panama',
            'PE' => 'Peru',
            'ZA' => 'South Africa',
            'LK' => 'Sri Lanka',
            'TH' => 'Thailand',
            'TT' => 'Trinidad and Tobago',
            'AE' => 'United Arab Emirates',
            'TZ' => 'Tanzania',
        ];

        // Level 1 - Exercise Normal Precautions (common destinations)
        $level1 = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            'BE' => 'Belgium',
            'NL' => 'Netherlands',
            'CH' => 'Switzerland',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'TW' => 'Taiwan',
            'SG' => 'Singapore',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'PT' => 'Portugal',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'GR' => 'Greece',
            'GH' => 'Ghana',
            'VN' => 'Vietnam',
        ];

        // Insert Level 4
        foreach ($level4 as $code => $name) {
            CountryTravelAdvisory::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $name,
                    'advisory_level' => '4',
                    'advisory_title' => 'Do Not Travel',
                    'is_prohibited' => in_array($code, $prohibited),
                    'advisory_summary' => 'Do not travel due to crime, civil unrest, kidnapping, terrorism, or armed conflict.',
                    'last_updated' => $now,
                ]
            );
        }

        // Insert Level 3
        foreach ($level3 as $code => $name) {
            CountryTravelAdvisory::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $name,
                    'advisory_level' => '3',
                    'advisory_title' => 'Reconsider Travel',
                    'is_prohibited' => in_array($code, $prohibited),
                    'advisory_summary' => 'Reconsider travel due to crime, terrorism, civil unrest, or other serious risks.',
                    'last_updated' => $now,
                ]
            );
        }

        // Insert Level 2
        foreach ($level2 as $code => $name) {
            CountryTravelAdvisory::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $name,
                    'advisory_level' => '2',
                    'advisory_title' => 'Exercise Increased Caution',
                    'is_prohibited' => in_array($code, $prohibited),
                    'advisory_summary' => 'Exercise increased caution due to crime, terrorism, or other risks.',
                    'last_updated' => $now,
                ]
            );
        }

        // Insert Level 1
        foreach ($level1 as $code => $name) {
            CountryTravelAdvisory::updateOrCreate(
                ['country_code' => $code],
                [
                    'country_name' => $name,
                    'advisory_level' => '1',
                    'advisory_title' => 'Exercise Normal Precautions',
                    'is_prohibited' => false,
                    'advisory_summary' => null,
                    'last_updated' => $now,
                ]
            );
        }

        $this->command->info('Seeded '.CountryTravelAdvisory::count().' country travel advisories.');
    }
}
