<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use App\Models\UsState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GeographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Regions
        $this->seedRegions();

        // Seed Countries
        $this->seedCountries();

        // Seed US States and Territories
        $this->seedUsStates();
    }

    protected function seedRegions(): void
    {
        foreach (Region::getDefaults() as $region) {
            Region::firstOrCreate(
                ['slug' => $region['slug']],
                $region
            );
        }

        $this->command->info('Regions seeded.');
    }

    protected function seedUsStates(): void
    {
        $sortOrder = 1;
        foreach (UsState::getDefaults() as $state) {
            UsState::firstOrCreate(
                ['abbreviation' => $state['abbreviation']],
                array_merge($state, [
                    'slug' => Str::slug($state['name']),
                    'sort_order' => $sortOrder++,
                ])
            );
        }

        $this->command->info('US States and Territories seeded.');
    }

    protected function seedCountries(): void
    {
        $countries = $this->getCountriesData();

        foreach ($countries as $countryData) {
            $region = Region::where('slug', $countryData['region_slug'])->first();
            if (! $region) {
                continue;
            }

            Country::firstOrCreate(
                ['iso_code' => $countryData['iso_code']],
                [
                    'region_id' => $region->id,
                    'name' => $countryData['name'],
                    'slug' => Str::slug($countryData['name']),
                    'iso_code' => $countryData['iso_code'],
                    'iso_code_3' => $countryData['iso_code_3'] ?? null,
                ]
            );
        }

        $this->command->info('Countries seeded.');
    }

    protected function getCountriesData(): array
    {
        return [
            // North America
            ['name' => 'Canada', 'iso_code' => 'CA', 'iso_code_3' => 'CAN', 'region_slug' => 'north-america'],
            ['name' => 'Mexico', 'iso_code' => 'MX', 'iso_code_3' => 'MEX', 'region_slug' => 'north-america'],
            ['name' => 'United States', 'iso_code' => 'US', 'iso_code_3' => 'USA', 'region_slug' => 'north-america'],

            // Latin America
            ['name' => 'Argentina', 'iso_code' => 'AR', 'iso_code_3' => 'ARG', 'region_slug' => 'latin-america'],
            ['name' => 'Bolivia', 'iso_code' => 'BO', 'iso_code_3' => 'BOL', 'region_slug' => 'latin-america'],
            ['name' => 'Brazil', 'iso_code' => 'BR', 'iso_code_3' => 'BRA', 'region_slug' => 'latin-america'],
            ['name' => 'Chile', 'iso_code' => 'CL', 'iso_code_3' => 'CHL', 'region_slug' => 'latin-america'],
            ['name' => 'Colombia', 'iso_code' => 'CO', 'iso_code_3' => 'COL', 'region_slug' => 'latin-america'],
            ['name' => 'Costa Rica', 'iso_code' => 'CR', 'iso_code_3' => 'CRI', 'region_slug' => 'latin-america'],
            ['name' => 'Ecuador', 'iso_code' => 'EC', 'iso_code_3' => 'ECU', 'region_slug' => 'latin-america'],
            ['name' => 'El Salvador', 'iso_code' => 'SV', 'iso_code_3' => 'SLV', 'region_slug' => 'latin-america'],
            ['name' => 'Guatemala', 'iso_code' => 'GT', 'iso_code_3' => 'GTM', 'region_slug' => 'latin-america'],
            ['name' => 'Honduras', 'iso_code' => 'HN', 'iso_code_3' => 'HND', 'region_slug' => 'latin-america'],
            ['name' => 'Nicaragua', 'iso_code' => 'NI', 'iso_code_3' => 'NIC', 'region_slug' => 'latin-america'],
            ['name' => 'Panama', 'iso_code' => 'PA', 'iso_code_3' => 'PAN', 'region_slug' => 'latin-america'],
            ['name' => 'Paraguay', 'iso_code' => 'PY', 'iso_code_3' => 'PRY', 'region_slug' => 'latin-america'],
            ['name' => 'Peru', 'iso_code' => 'PE', 'iso_code_3' => 'PER', 'region_slug' => 'latin-america'],
            ['name' => 'Uruguay', 'iso_code' => 'UY', 'iso_code_3' => 'URY', 'region_slug' => 'latin-america'],
            ['name' => 'Venezuela', 'iso_code' => 'VE', 'iso_code_3' => 'VEN', 'region_slug' => 'latin-america'],

            // Caribbean
            ['name' => 'Bahamas', 'iso_code' => 'BS', 'iso_code_3' => 'BHS', 'region_slug' => 'caribbean'],
            ['name' => 'Barbados', 'iso_code' => 'BB', 'iso_code_3' => 'BRB', 'region_slug' => 'caribbean'],
            ['name' => 'Cuba', 'iso_code' => 'CU', 'iso_code_3' => 'CUB', 'region_slug' => 'caribbean'],
            ['name' => 'Dominican Republic', 'iso_code' => 'DO', 'iso_code_3' => 'DOM', 'region_slug' => 'caribbean'],
            ['name' => 'Haiti', 'iso_code' => 'HT', 'iso_code_3' => 'HTI', 'region_slug' => 'caribbean'],
            ['name' => 'Jamaica', 'iso_code' => 'JM', 'iso_code_3' => 'JAM', 'region_slug' => 'caribbean'],
            ['name' => 'Trinidad and Tobago', 'iso_code' => 'TT', 'iso_code_3' => 'TTO', 'region_slug' => 'caribbean'],

            // Europe
            ['name' => 'Austria', 'iso_code' => 'AT', 'iso_code_3' => 'AUT', 'region_slug' => 'europe'],
            ['name' => 'Belgium', 'iso_code' => 'BE', 'iso_code_3' => 'BEL', 'region_slug' => 'europe'],
            ['name' => 'Czech Republic', 'iso_code' => 'CZ', 'iso_code_3' => 'CZE', 'region_slug' => 'europe'],
            ['name' => 'Denmark', 'iso_code' => 'DK', 'iso_code_3' => 'DNK', 'region_slug' => 'europe'],
            ['name' => 'Finland', 'iso_code' => 'FI', 'iso_code_3' => 'FIN', 'region_slug' => 'europe'],
            ['name' => 'France', 'iso_code' => 'FR', 'iso_code_3' => 'FRA', 'region_slug' => 'europe'],
            ['name' => 'Germany', 'iso_code' => 'DE', 'iso_code_3' => 'DEU', 'region_slug' => 'europe'],
            ['name' => 'Greece', 'iso_code' => 'GR', 'iso_code_3' => 'GRC', 'region_slug' => 'europe'],
            ['name' => 'Hungary', 'iso_code' => 'HU', 'iso_code_3' => 'HUN', 'region_slug' => 'europe'],
            ['name' => 'Ireland', 'iso_code' => 'IE', 'iso_code_3' => 'IRL', 'region_slug' => 'europe'],
            ['name' => 'Italy', 'iso_code' => 'IT', 'iso_code_3' => 'ITA', 'region_slug' => 'europe'],
            ['name' => 'Netherlands', 'iso_code' => 'NL', 'iso_code_3' => 'NLD', 'region_slug' => 'europe'],
            ['name' => 'Norway', 'iso_code' => 'NO', 'iso_code_3' => 'NOR', 'region_slug' => 'europe'],
            ['name' => 'Poland', 'iso_code' => 'PL', 'iso_code_3' => 'POL', 'region_slug' => 'europe'],
            ['name' => 'Portugal', 'iso_code' => 'PT', 'iso_code_3' => 'PRT', 'region_slug' => 'europe'],
            ['name' => 'Romania', 'iso_code' => 'RO', 'iso_code_3' => 'ROU', 'region_slug' => 'europe'],
            ['name' => 'Spain', 'iso_code' => 'ES', 'iso_code_3' => 'ESP', 'region_slug' => 'europe'],
            ['name' => 'Sweden', 'iso_code' => 'SE', 'iso_code_3' => 'SWE', 'region_slug' => 'europe'],
            ['name' => 'Switzerland', 'iso_code' => 'CH', 'iso_code_3' => 'CHE', 'region_slug' => 'europe'],
            ['name' => 'Ukraine', 'iso_code' => 'UA', 'iso_code_3' => 'UKR', 'region_slug' => 'europe'],
            ['name' => 'United Kingdom', 'iso_code' => 'GB', 'iso_code_3' => 'GBR', 'region_slug' => 'europe'],

            // Africa
            ['name' => 'Algeria', 'iso_code' => 'DZ', 'iso_code_3' => 'DZA', 'region_slug' => 'africa'],
            ['name' => 'Egypt', 'iso_code' => 'EG', 'iso_code_3' => 'EGY', 'region_slug' => 'africa'],
            ['name' => 'Ethiopia', 'iso_code' => 'ET', 'iso_code_3' => 'ETH', 'region_slug' => 'africa'],
            ['name' => 'Ghana', 'iso_code' => 'GH', 'iso_code_3' => 'GHA', 'region_slug' => 'africa'],
            ['name' => 'Kenya', 'iso_code' => 'KE', 'iso_code_3' => 'KEN', 'region_slug' => 'africa'],
            ['name' => 'Morocco', 'iso_code' => 'MA', 'iso_code_3' => 'MAR', 'region_slug' => 'africa'],
            ['name' => 'Nigeria', 'iso_code' => 'NG', 'iso_code_3' => 'NGA', 'region_slug' => 'africa'],
            ['name' => 'Rwanda', 'iso_code' => 'RW', 'iso_code_3' => 'RWA', 'region_slug' => 'africa'],
            ['name' => 'Senegal', 'iso_code' => 'SN', 'iso_code_3' => 'SEN', 'region_slug' => 'africa'],
            ['name' => 'South Africa', 'iso_code' => 'ZA', 'iso_code_3' => 'ZAF', 'region_slug' => 'africa'],
            ['name' => 'Tanzania', 'iso_code' => 'TZ', 'iso_code_3' => 'TZA', 'region_slug' => 'africa'],
            ['name' => 'Tunisia', 'iso_code' => 'TN', 'iso_code_3' => 'TUN', 'region_slug' => 'africa'],
            ['name' => 'Uganda', 'iso_code' => 'UG', 'iso_code_3' => 'UGA', 'region_slug' => 'africa'],

            // Middle East
            ['name' => 'Bahrain', 'iso_code' => 'BH', 'iso_code_3' => 'BHR', 'region_slug' => 'middle-east'],
            ['name' => 'Iran', 'iso_code' => 'IR', 'iso_code_3' => 'IRN', 'region_slug' => 'middle-east'],
            ['name' => 'Iraq', 'iso_code' => 'IQ', 'iso_code_3' => 'IRQ', 'region_slug' => 'middle-east'],
            ['name' => 'Israel', 'iso_code' => 'IL', 'iso_code_3' => 'ISR', 'region_slug' => 'middle-east'],
            ['name' => 'Jordan', 'iso_code' => 'JO', 'iso_code_3' => 'JOR', 'region_slug' => 'middle-east'],
            ['name' => 'Kuwait', 'iso_code' => 'KW', 'iso_code_3' => 'KWT', 'region_slug' => 'middle-east'],
            ['name' => 'Lebanon', 'iso_code' => 'LB', 'iso_code_3' => 'LBN', 'region_slug' => 'middle-east'],
            ['name' => 'Oman', 'iso_code' => 'OM', 'iso_code_3' => 'OMN', 'region_slug' => 'middle-east'],
            ['name' => 'Qatar', 'iso_code' => 'QA', 'iso_code_3' => 'QAT', 'region_slug' => 'middle-east'],
            ['name' => 'Saudi Arabia', 'iso_code' => 'SA', 'iso_code_3' => 'SAU', 'region_slug' => 'middle-east'],
            ['name' => 'Syria', 'iso_code' => 'SY', 'iso_code_3' => 'SYR', 'region_slug' => 'middle-east'],
            ['name' => 'Turkey', 'iso_code' => 'TR', 'iso_code_3' => 'TUR', 'region_slug' => 'middle-east'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'iso_code_3' => 'ARE', 'region_slug' => 'middle-east'],
            ['name' => 'Yemen', 'iso_code' => 'YE', 'iso_code_3' => 'YEM', 'region_slug' => 'middle-east'],

            // Asia
            ['name' => 'Bangladesh', 'iso_code' => 'BD', 'iso_code_3' => 'BGD', 'region_slug' => 'asia'],
            ['name' => 'Cambodia', 'iso_code' => 'KH', 'iso_code_3' => 'KHM', 'region_slug' => 'asia'],
            ['name' => 'China', 'iso_code' => 'CN', 'iso_code_3' => 'CHN', 'region_slug' => 'asia'],
            ['name' => 'Hong Kong', 'iso_code' => 'HK', 'iso_code_3' => 'HKG', 'region_slug' => 'asia'],
            ['name' => 'India', 'iso_code' => 'IN', 'iso_code_3' => 'IND', 'region_slug' => 'asia'],
            ['name' => 'Indonesia', 'iso_code' => 'ID', 'iso_code_3' => 'IDN', 'region_slug' => 'asia'],
            ['name' => 'Japan', 'iso_code' => 'JP', 'iso_code_3' => 'JPN', 'region_slug' => 'asia'],
            ['name' => 'Kazakhstan', 'iso_code' => 'KZ', 'iso_code_3' => 'KAZ', 'region_slug' => 'asia'],
            ['name' => 'Malaysia', 'iso_code' => 'MY', 'iso_code_3' => 'MYS', 'region_slug' => 'asia'],
            ['name' => 'Myanmar', 'iso_code' => 'MM', 'iso_code_3' => 'MMR', 'region_slug' => 'asia'],
            ['name' => 'Nepal', 'iso_code' => 'NP', 'iso_code_3' => 'NPL', 'region_slug' => 'asia'],
            ['name' => 'Pakistan', 'iso_code' => 'PK', 'iso_code_3' => 'PAK', 'region_slug' => 'asia'],
            ['name' => 'Philippines', 'iso_code' => 'PH', 'iso_code_3' => 'PHL', 'region_slug' => 'asia'],
            ['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code_3' => 'SGP', 'region_slug' => 'asia'],
            ['name' => 'South Korea', 'iso_code' => 'KR', 'iso_code_3' => 'KOR', 'region_slug' => 'asia'],
            ['name' => 'Sri Lanka', 'iso_code' => 'LK', 'iso_code_3' => 'LKA', 'region_slug' => 'asia'],
            ['name' => 'Taiwan', 'iso_code' => 'TW', 'iso_code_3' => 'TWN', 'region_slug' => 'asia'],
            ['name' => 'Thailand', 'iso_code' => 'TH', 'iso_code_3' => 'THA', 'region_slug' => 'asia'],
            ['name' => 'Vietnam', 'iso_code' => 'VN', 'iso_code_3' => 'VNM', 'region_slug' => 'asia'],

            // Oceania
            ['name' => 'Australia', 'iso_code' => 'AU', 'iso_code_3' => 'AUS', 'region_slug' => 'oceania'],
            ['name' => 'Fiji', 'iso_code' => 'FJ', 'iso_code_3' => 'FJI', 'region_slug' => 'oceania'],
            ['name' => 'New Zealand', 'iso_code' => 'NZ', 'iso_code_3' => 'NZL', 'region_slug' => 'oceania'],
            ['name' => 'Papua New Guinea', 'iso_code' => 'PG', 'iso_code_3' => 'PNG', 'region_slug' => 'oceania'],
        ];
    }
}
