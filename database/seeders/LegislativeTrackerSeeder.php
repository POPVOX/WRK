<?php

namespace Database\Seeders;

use App\Models\LegislativeReport;
use App\Models\ReportingRequirement;
use Illuminate\Database\Seeder;

class LegislativeTrackerSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample FY2026 House Report
        $houseReport = LegislativeReport::create([
            'fiscal_year' => 'FY2026',
            'report_type' => 'house',
            'report_number' => '119-178',
            'title' => 'Legislative Branch Appropriations Act, 2026',
            'enactment_date' => now()->subDays(30),
            'uploaded_by' => 1,
            'notes' => 'Sample data for demonstration purposes.',
        ]);

        // Sample requirements
        $requirements = [
            [
                'category' => 'new',
                'report_title' => 'Annual Budget Justification Materials',
                'responsible_agency' => 'Architect of the Capitol',
                'timeline_type' => 'days_from_enactment',
                'timeline_value' => 30,
                'description' => 'The Committee directs the Architect of the Capitol to submit detailed budget justification materials for all major capital projects, including updated cost estimates and timelines.',
                'reporting_recipients' => 'House and Senate Committees on Appropriations',
                'source_page_reference' => 'p. 12',
                'status' => 'pending',
            ],
            [
                'category' => 'new',
                'report_title' => 'Capitol Police Staffing Analysis',
                'responsible_agency' => 'U.S. Capitol Police',
                'timeline_type' => 'days_from_enactment',
                'timeline_value' => 60,
                'description' => 'The Committee requests a comprehensive staffing analysis, including current vacancies, recruitment efforts, and projected needs for the next fiscal year.',
                'reporting_recipients' => 'House Committee on Appropriations, Subcommittee on Legislative Branch',
                'source_page_reference' => 'p. 28',
                'status' => 'pending',
            ],
            [
                'category' => 'new',
                'report_title' => 'Cybersecurity Assessment Report',
                'responsible_agency' => 'Chief Administrative Officer',
                'timeline_type' => 'days_from_enactment',
                'timeline_value' => 90,
                'description' => 'The Committee directs the CAO to provide a detailed assessment of cybersecurity vulnerabilities and mitigation efforts across House systems.',
                'reporting_recipients' => 'Committee on House Administration',
                'source_page_reference' => 'p. 45',
                'status' => 'in_progress',
            ],
            [
                'category' => 'ongoing',
                'report_title' => 'Quarterly Financial Status Update',
                'responsible_agency' => 'Government Accountability Office',
                'timeline_type' => 'quarterly',
                'timeline_value' => null,
                'description' => 'The GAO shall continue to provide quarterly updates on the financial status of legislative branch agencies.',
                'reporting_recipients' => 'House and Senate Committees on Appropriations',
                'source_page_reference' => 'p. 67',
                'status' => 'pending',
            ],
            [
                'category' => 'prior_year',
                'report_title' => 'Library of Congress Digitization Progress',
                'responsible_agency' => 'Library of Congress',
                'timeline_type' => 'annual',
                'timeline_value' => null,
                'description' => 'Continuation of prior year mandate requiring annual updates on digitization efforts and public access improvements.',
                'reporting_recipients' => 'Joint Committee on the Library',
                'source_page_reference' => 'p. 89',
                'status' => 'submitted',
            ],
        ];

        foreach ($requirements as $reqData) {
            $requirement = ReportingRequirement::create([
                'legislative_report_id' => $houseReport->id,
                ...$reqData,
            ]);

            // Calculate due date
            $dueDate = $requirement->calculateDueDate();
            if ($dueDate) {
                $requirement->update(['due_date' => $dueDate]);
                $requirement->createReminders();
            }
        }

        // Create sample FY2026 Senate Report
        $senateReport = LegislativeReport::create([
            'fiscal_year' => 'FY2026',
            'report_type' => 'senate',
            'report_number' => '119-92',
            'title' => 'Legislative Branch Appropriations Act, 2026 (Senate)',
            'enactment_date' => now()->subDays(15),
            'uploaded_by' => 1,
        ]);

        $senateRequirements = [
            [
                'category' => 'new',
                'report_title' => 'Senate Sergeant at Arms Security Review',
                'responsible_agency' => 'Senate Sergeant at Arms',
                'timeline_type' => 'days_from_enactment',
                'timeline_value' => 45,
                'description' => 'Review of security protocols and recommendations for improvements.',
                'reporting_recipients' => 'Senate Committee on Rules and Administration',
                'source_page_reference' => 'p. 8',
                'status' => 'pending',
            ],
            [
                'category' => 'new',
                'report_title' => 'CRS Research Services Utilization Report',
                'responsible_agency' => 'Congressional Research Service',
                'timeline_type' => 'days_from_enactment',
                'timeline_value' => 120,
                'description' => 'Analysis of research request patterns and service delivery metrics.',
                'reporting_recipients' => 'Senate and House Leadership',
                'source_page_reference' => 'p. 34',
                'status' => 'pending',
            ],
        ];

        foreach ($senateRequirements as $reqData) {
            $requirement = ReportingRequirement::create([
                'legislative_report_id' => $senateReport->id,
                ...$reqData,
            ]);

            $dueDate = $requirement->calculateDueDate();
            if ($dueDate) {
                $requirement->update(['due_date' => $dueDate]);
                $requirement->createReminders();
            }
        }

        $this->command->info('Created sample Legislative Tracker data: 2 reports with 7 requirements.');
    }
}

