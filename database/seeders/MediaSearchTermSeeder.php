<?php

namespace Database\Seeders;

use App\Models\MediaSearchTerm;
use Illuminate\Database\Seeder;

class MediaSearchTermSeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            '"POPVOX Foundation"',
            '"POPVOX" Congress',
            '"Marci Harris" POPVOX',
            '"Future-Proofing Congress"',
            '"REBOOT CONGRESS"',
            'POPVOX "congressional modernization"',
            '"Aubrey Wilson" Congress',
            '"congressional capacity"',
            'POPVOX civictech',
        ];

        foreach ($terms as $term) {
            MediaSearchTerm::firstOrCreate(['term' => $term]);
        }
    }
}
