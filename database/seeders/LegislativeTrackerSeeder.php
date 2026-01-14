<?php

namespace Database\Seeders;

use App\Models\LegislativeReport;
use App\Models\ReportingRequirement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LegislativeTrackerSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('2027 Approps Tracking.csv');

        if (!File::exists($csvPath)) {
            $this->command->error('CSV file not found: 2027 Approps Tracking.csv');
            return;
        }

        // Create FY2027 House Report
        $houseReport = LegislativeReport::create([
            'fiscal_year' => 'FY2027',
            'report_type' => 'house',
            'report_number' => '119-XXX',
            'title' => 'FY2027 Appropriations Member Outreach Tracking',
            'enactment_date' => null,
            'uploaded_by' => 1,
            'notes' => 'Imported from 2027 Approps Tracking.csv - Tracking member outreach for FY2027 appropriations requests.',
        ]);

        // Create FY2027 Senate Report
        $senateReport = LegislativeReport::create([
            'fiscal_year' => 'FY2027',
            'report_type' => 'senate',
            'report_number' => '119-XXX',
            'title' => 'FY2027 Appropriations Member Outreach Tracking (Senate)',
            'enactment_date' => null,
            'uploaded_by' => 1,
            'notes' => 'Imported from 2027 Approps Tracking.csv - Tracking member outreach for FY2027 appropriations requests.',
        ]);

        // Parse CSV
        $csv = array_map('str_getcsv', file($csvPath));
        $houseCount = 0;
        $senateCount = 0;

        foreach ($csv as $row) {
            // Skip empty rows
            if (empty($row[0]) || count($row) < 6) {
                continue;
            }

            $chamber = trim($row[0] ?? '');
            $memberName = trim($row[1] ?? '');
            $interested = trim($row[2] ?? '');
            $status = trim($row[4] ?? 'Not Started');
            $contactName = trim($row[5] ?? '');
            $contactEmail = trim($row[6] ?? '');
            $notes = trim($row[10] ?? '');

            // Skip if no member name
            if (empty($memberName)) {
                continue;
            }

            // Determine report and category
            $isHouse = stripos($chamber, 'House') !== false;
            $reportId = $isHouse ? $houseReport->id : $senateReport->id;

            // Determine category based on interest
            $category = 'new';
            if (stripos($interested, 'Leg Branch') !== false) {
                $category = 'ongoing'; // Leg Branch specific
            } elseif (stripos($interested, 'Yes') !== false) {
                $category = 'new';
            } else {
                $category = 'prior_year'; // Not yet engaged
            }

            // Map status
            $dbStatus = match (strtolower($status)) {
                'not started' => 'pending',
                'in progress' => 'in_progress',
                'complete', 'completed', 'done' => 'submitted',
                default => 'pending',
            };

            // Build description
            $description = "Appropriations outreach tracking for {$memberName}.";
            if (!empty($contactName)) {
                $description .= "\nContact: {$contactName}";
            }
            if (!empty($contactEmail)) {
                $description .= "\nEmail: {$contactEmail}";
            }
            if (!empty($notes) && $notes !== 'NA') {
                $description .= "\nNotes: {$notes}";
            }

            // Create requirement
            ReportingRequirement::create([
                'legislative_report_id' => $reportId,
                'category' => $category,
                'report_title' => "FY2027 Outreach: {$memberName}",
                'responsible_agency' => $isHouse ? 'House Member Office' : 'Senate Member Office',
                'timeline_type' => 'specific_date',
                'timeline_value' => null,
                'due_date' => now()->addDays(30), // Default 30-day follow-up
                'description' => $description,
                'reporting_recipients' => $memberName,
                'source_page_reference' => $interested ?: null,
                'status' => $dbStatus,
                'notes' => $notes !== 'NA' ? $notes : null,
            ]);

            if ($isHouse) {
                $houseCount++;
            } else {
                $senateCount++;
            }
        }

        $this->command->info("Imported FY2027 Appropriations Tracking data:");
        $this->command->info("  - House: {$houseCount} members");
        $this->command->info("  - Senate: {$senateCount} members");
        $this->command->info("  - Total: " . ($houseCount + $senateCount) . " tracking items");
    }
}

