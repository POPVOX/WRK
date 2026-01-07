<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'name' => 'Casework Navigator newsletter',
                'scope' => 'US',
                'description' => 'Biweekly CW Navigator newsletter; agency roundup',
                'lead' => 'Caitlin',
                'start_date' => '2026-01-05',
                'target_end_date' => '2026-01-12',
                'status' => 'on_hold', // Pending
                'tags' => ['Bridge-building (exec-leg/R+D)', 'Interbranch feedback loops', 'Bipartisan modernization alignment', 'Improvements to public-facing services'],
            ],
            [
                'name' => 'Rep Bodies Vol II',
                'scope' => 'Global',
                'description' => 'Vol II of Rep bodies launch',
                'lead' => 'Marci',
                'start_date' => '2026-01-01',
                'target_end_date' => '2026-01-16',
                'status' => 'active',
                'tags' => [],
            ],
            [
                'name' => 'Futureproofing newsletter',
                'scope' => 'US',
                'description' => 'Prepping the monthly FP newsletter',
                'lead' => 'Danielle',
                'start_date' => '2026-01-19',
                'target_end_date' => '2026-01-26',
                'status' => 'active',
                'tags' => [],
            ],
            [
                'name' => 'HDS data management workshop',
                'scope' => 'US',
                'description' => "CW Navigator workshop featuring HDS' CaseCompass data scheme; tips on data management from all 3 CRMs",
                'lead' => 'Anne',
                'start_date' => '2026-01-02',
                'target_end_date' => '2026-02-05',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Departure Dialogues report launch',
                'scope' => 'US',
                'description' => 'Launching specific + meta reports for DD project',
                'lead' => 'Anne',
                'start_date' => '2026-01-19',
                'target_end_date' => '2026-02-27',
                'status' => 'active',
                'tags' => [],
            ],
            [
                'name' => 'Intro: rebooting Congress project',
                'scope' => 'US',
                'description' => 'Pacing problem',
                'url' => 'https://www.notion.so/2c790b4f36d38014b310fcdeb5840dc7',
                'lead' => 'Marci',
                'start_date' => '2026-01-05',
                'target_end_date' => '2026-03-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Rebooting Congress launch',
                'scope' => 'US',
                'description' => "Kick off Rebooting report/vision series with an initial package including:\n1. The case for a reboot\n2. The Member experience\n3. Future of staffing\n4. Full-stack constituent engagement",
                'lead' => 'Anne',
                'start_date' => '2026-01-01',
                'target_end_date' => '2026-03-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Q1 Hill event: kicking off the Rebooting Congress launch and Gavel In Season II',
                'scope' => 'US',
                'description' => "Salon: the need for a reboot\nHill briefing: future of staffing\nDistrict briefing: full-stack const engage",
                'lead' => 'Anne',
                'start_date' => '2026-01-01',
                'target_end_date' => '2026-03-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Website reboot',
                'scope' => 'Comms',
                'description' => 'Rebooting our website to reflect our work',
                'lead' => 'Anne',
                'start_date' => '2026-01-01',
                'target_end_date' => '2026-03-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Gavel In podcast',
                'scope' => 'US',
                'description' => "Season II of Gavel In to launch concurrently with member experience chapter of Rebooting, focusing on perspectives from this year's class of Freshman members",
                'lead' => 'Aubrey',
                'start_date' => '2026-01-09',
                'target_end_date' => '2026-03-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'AI workshop/resource',
                'scope' => 'Global',
                'description' => 'Quarterly addition to AI resources/training schedule',
                'lead' => 'Aubrey',
                'start_date' => '2026-01-12',
                'target_end_date' => '2026-03-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Launch Hansard tools for DPP',
                'scope' => 'Global',
                'description' => 'Additional features for DPP project',
                'lead' => 'Aubrey',
                'start_date' => '2026-02-01',
                'target_end_date' => '2026-03-31',
                'status' => 'active',
                'tags' => [],
            ],
            [
                'name' => 'Appropriations work',
                'scope' => 'US',
                'description' => 'Pushing priorities groundwork-building for big ideas through annual leg branch approps bill',
                'lead' => 'Danielle',
                'start_date' => '2026-01-19',
                'target_end_date' => '2026-04-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Launch state modernization newsletter',
                'scope' => 'US',
                'description' => 'Building on ModParl approach to develop a network of state-level modernizers',
                'lead' => 'Caitlin',
                'start_date' => '2026-04-01',
                'target_end_date' => '2026-05-01',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Reconstituting launch',
                'scope' => 'Global',
                'description' => 'Publication of the Reconstituting book',
                'lead' => 'Marci',
                'start_date' => '2026-05-01',
                'target_end_date' => '2026-05-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'US/UK fellowship program',
                'scope' => 'Global',
                'description' => 'Launching international learning fellowship connecting US and UK modernizers',
                'lead' => 'Chloe',
                'start_date' => '2026-04-01',
                'target_end_date' => '2026-06-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Q2 Hill event',
                'scope' => 'US',
                'description' => 'TBD',
                'lead' => 'Danielle',
                'start_date' => '2026-04-01',
                'target_end_date' => '2026-06-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'NCSL presence',
                'scope' => 'US',
                'description' => 'Booth and side events for our US-based team around NCSL in Chicago',
                'lead' => 'Caitlin',
                'start_date' => '2026-05-01',
                'target_end_date' => '2026-07-27',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'African Digital Parliaments Project',
                'scope' => 'Global',
                'description' => 'Translating CDPP to African parliaments',
                'lead' => 'Aubrey',
                'start_date' => '2026-05-01',
                'target_end_date' => '2026-08-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Advocacy around Rules package for 120th Congress',
                'scope' => 'US',
                'description' => 'Releasing package of rules for 120th',
                'lead' => 'Danielle',
                'start_date' => '2026-07-15',
                'target_end_date' => '2026-08-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Q3 Hill event',
                'scope' => 'US',
                'description' => 'TBD',
                'lead' => 'Anne',
                'start_date' => '2026-07-01',
                'target_end_date' => '2026-09-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'IPU/CPA partnerships for Parllink',
                'scope' => 'Global',
                'description' => 'Formalizing partnerships with IPU/CPA on launching/franchising Parllink tools',
                'lead' => 'Aubrey',
                'start_date' => '2026-08-03',
                'target_end_date' => '2026-10-30',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'NMO activities',
                'scope' => 'US',
                'description' => 'Plan and run with activities to support new Members and incept ideas around a rebooted Congress',
                'lead' => 'Anne',
                'start_date' => '2026-09-01',
                'target_end_date' => '2026-12-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Q4 year-end Hill event',
                'scope' => 'US',
                'description' => 'TBD',
                'lead' => 'Danielle',
                'start_date' => '2026-10-01',
                'target_end_date' => '2026-12-31',
                'status' => 'on_hold', // Pending
                'tags' => [],
            ],
            [
                'name' => 'Caribbean Digital Parliaments Project',
                'scope' => 'Global',
                'description' => 'Building digital legislative infrastructure for small parliaments in the Caribbean',
                'lead' => 'Aubrey',
                'start_date' => null,
                'target_end_date' => null,
                'status' => 'active',
                'tags' => [],
            ],
        ];

        foreach ($projects as $projectData) {
            Project::updateOrCreate(
                ['name' => $projectData['name']],
                $projectData
            );
        }

        $this->command->info('Imported '.count($projects).' projects.');
    }
}
