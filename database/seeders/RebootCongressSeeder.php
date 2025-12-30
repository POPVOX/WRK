<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectEvent;
use App\Models\ProjectPublication;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RebootCongressSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update the REBOOT CONGRESS 2026 project
        $project = Project::updateOrCreate(
            ['name' => 'REBOOT CONGRESS 2026'],
            [
                'description' => 'Rebuilding Legislative Capacity for the AI Era. A year-long research and engagement initiative culminating in comprehensive recommendations for the 120th Congress (January 2027).',
                'status' => 'active',
                'is_initiative' => true,
                'project_path' => 'REBOOT CONGRESS 2026',
                'scope' => 'Major Initiative',
                'lead' => 'POPVOX Foundation',
                'start_date' => Carbon::create(2026, 1, 1),
                'target_end_date' => Carbon::create(2026, 12, 31),
                'success_metrics' => [
                    ['title' => 'Report adopted by modernization champion', 'completed' => false],
                    ['title' => '3+ committee staff briefings', 'completed' => false],
                    ['title' => '50+ Hill staff engaged through events', 'completed' => false],
                    ['title' => '2+ prototype tools with congressional office pilots', 'completed' => false],
                    ['title' => '5+ partner organization endorsements', 'completed' => false],
                    ['title' => 'Trade press coverage establishing POPVOX as leader on congressional AI', 'completed' => false],
                ],
                'tags' => ['congress', 'ai', 'modernization', 'policy', 'research'],
            ]
        );

        // Create publications (chapters)
        $publications = [
            [
                'sort_order' => 0,
                'title' => 'Representative Bodies for the AI Era (2nd Edition)',
                'type' => 'report',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 1, 15),
                'description' => 'Foundation document establishing the framework for AI-enhanced legislative operations.',
            ],
            [
                'sort_order' => 1,
                'title' => 'Chapter 1: The Legislative Data Crisis',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 2, 15),
                'description' => 'Quantifies the problem of data accessibility in Congress and establishes the stakes.',
            ],
            [
                'sort_order' => 2,
                'title' => 'Chapter 2: How We Got Here',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 3, 15),
                'description' => 'Historical analysis of legislative data infrastructure and key inflection points.',
            ],
            [
                'sort_order' => 3,
                'title' => 'Chapter 3: AI Fundamentals for Legislative Staff',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 4, 15),
                'description' => 'Practical, non-technical introduction to AI capabilities and limitations for legislative context.',
            ],
            [
                'sort_order' => 4,
                'title' => 'Chapter 4: The Decision Trace',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 5, 15),
                'description' => 'The fundamental innovation enabling AI-enhanced legislative operations.',
            ],
            [
                'sort_order' => 5,
                'title' => 'Chapter 5: Security Architecture',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 6, 15),
                'description' => 'Comprehensive framework for deploying AI-enabled systems in the congressional environment.',
            ],
            [
                'sort_order' => 6,
                'title' => 'Chapter 6: Constituent Services Transformed',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 7, 15),
                'description' => 'How AI-enabled systems could transform constituent casework.',
            ],
            [
                'sort_order' => 7,
                'title' => 'Chapter 7: Cross-Office Coordination',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 8, 15),
                'description' => 'How AI agents could enable new forms of coordination across congressional offices.',
            ],
            [
                'sort_order' => 8,
                'title' => 'Chapter 8: Case Study - Disaster Response',
                'type' => 'case_study',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 9, 15),
                'description' => 'Detailed case study demonstrating AI-enabled coordination in disaster response.',
            ],
            [
                'sort_order' => 9,
                'title' => 'Chapter 9: Agency Integration and Oversight',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 10, 15),
                'description' => 'How AI-enabled systems could transform Congress\'s relationship with executive agencies.',
            ],
            [
                'sort_order' => 10,
                'title' => 'Chapter 10: Governance Framework',
                'type' => 'chapter',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 11, 15),
                'description' => 'Framework for institutional AI governance in Congress.',
            ],
            [
                'sort_order' => 11,
                'title' => 'Final Report: Recommendations for the 120th Congress',
                'type' => 'report',
                'status' => 'idea',
                'target_date' => Carbon::create(2026, 12, 15),
                'description' => 'Culminating report with specific, actionable recommendations for rules changes, appropriations, and governance.',
            ],
        ];

        foreach ($publications as $pubData) {
            ProjectPublication::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $pubData['title'],
                ],
                $pubData
            );
        }

        // Create events
        $events = [
            [
                'title' => 'Representative Bodies Launch Event',
                'type' => 'launch',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 1, 20, 14, 0),
                'location' => 'Capitol Hill / Hybrid',
                'target_attendees' => 30,
                'description' => 'Launch event for Representative Bodies 2nd Edition and announcement of REBOOT CONGRESS initiative.',
            ],
            [
                'title' => 'Q1 Staff Event: Learning from Legislative History',
                'type' => 'staff_event',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 3, 20, 14, 0),
                'location' => 'Capitol Hill / Hybrid',
                'target_attendees' => 20,
                'description' => '90-minute session with Hill staff covering Chapter 1-2 findings with interactive discussion.',
            ],
            [
                'title' => 'Meetings Intel Tool Demo',
                'type' => 'demo',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 5, 22, 14, 0),
                'location' => 'TBD',
                'target_attendees' => 15,
                'description' => 'Live demonstration of Meetings Intel Tool with decision trace to interested staff.',
            ],
            [
                'title' => 'Q2 Staff Event: AI Security for Congressional Offices',
                'type' => 'staff_event',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 6, 19, 14, 0),
                'location' => 'Capitol Hill / Hybrid',
                'target_attendees' => 20,
                'description' => '2-hour session with Hill IT/security-adjacent staff. Security framework walkthrough.',
            ],
            [
                'title' => 'Q3 Staff Event: AI for Congressional Coordination',
                'type' => 'workshop',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 9, 18, 14, 0),
                'location' => 'Capitol Hill',
                'target_attendees' => 25,
                'description' => '2-hour workshop with simulation element. Walk through disaster coordination scenario.',
            ],
            [
                'title' => 'Stakeholder Review',
                'type' => 'briefing',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 11, 18, 10, 0),
                'location' => 'Virtual',
                'target_attendees' => 40,
                'description' => 'Draft final report circulation to all engaged stakeholders for validation and endorsement.',
            ],
            [
                'title' => 'Q4 Launch Event: Final Report Release',
                'type' => 'launch',
                'status' => 'planning',
                'event_date' => Carbon::create(2026, 12, 18, 10, 0),
                'location' => 'Capitol Hill or prestigious DC venue',
                'target_attendees' => 100,
                'description' => 'Major event (2-3 hours). Recommendations reveal, panel discussion. Maximum visibility for 120th Congress transition.',
            ],
        ];

        foreach ($events as $eventData) {
            ProjectEvent::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'title' => $eventData['title'],
                ],
                $eventData
            );
        }

        $this->command->info('REBOOT CONGRESS 2026 project seeded with ' . count($publications) . ' publications and ' . count($events) . ' events.');
    }
}
