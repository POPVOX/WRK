<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing projects
        Project::query()->delete();

        $projects = [
            // REBOOT CONGRESS - Main Initiative
            [
                'name' => 'REBOOT CONGRESS',
                'project_type' => 'initiative',
                'lead' => 'Marci',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    [
                        'name' => 'REBOOT CONGRESS REPORT',
                        'project_type' => 'publication',
                        'status' => 'active',
                        'children' => [
                            ['name' => 'The Member Experience', 'project_type' => 'chapter', 'lead' => 'Danielle', 'description' => 'Examines how Members are onboarded, equipped, and supported throughout their service—from new Member orientation through the tools and information systems that shape daily decision-making.'],
                            ['name' => 'The Congressional Workforce', 'project_type' => 'chapter', 'lead' => 'Danielle', 'description' => 'Addresses the staffing crisis head-on: compensation, retention, career paths, professional development, and the knowledge management systems needed to preserve institutional memory across inevitable turnover.'],
                            ['name' => 'Constituent Engagement', 'project_type' => 'chapter', 'lead' => 'Anne', 'description' => 'Reimagines the full stack of interactions between Congress and the public—from casework to policy input to civic education—for a digital age.'],
                            ['name' => 'Technology Governance', 'project_type' => 'chapter', 'lead' => 'Aubrey', 'description' => 'Tackles the procurement, development, and security challenges that have left Congress technologically behind, with specific attention to responsible AI adoption.'],
                            ['name' => 'Support Agencies Transformed', 'project_type' => 'chapter', 'lead' => 'Marci', 'description' => 'Envisions how CRS, CBO, GAO, and other congressional support offices can be integrated, modernized, and empowered to meet 21st-century demands.'],
                            ['name' => 'Oversight Reimagined', 'project_type' => 'chapter', 'lead' => 'Marci', 'description' => 'Proposes a new model for congressional oversight—one that emphasizes post-legislative scrutiny, real-time monitoring, and data-driven accountability over episodic hearings.'],
                            ['name' => 'Budgeting That Works', 'project_type' => 'chapter', 'lead' => 'Marci', 'description' => 'Confronts the dysfunction of the current budget process and offers reforms calibrated to an era that demands both fiscal discipline and rapid response capability.'],
                            ['name' => 'Committees and Procedure', 'project_type' => 'chapter', 'lead' => 'Marci', 'description' => 'Examines the committee system, floor procedures, and parliamentary practices that structure how Congress does its work—and how they might evolve.'],
                            ['name' => 'The Legislative Product', 'project_type' => 'chapter', 'lead' => 'Marci', 'description' => 'Focuses on law drafting, codification, and the legislative record—the documentary output that is Congress\'s lasting contribution to American governance.'],
                            ['name' => 'Interbranch Relations', 'project_type' => 'chapter', 'lead' => 'Anne', 'description' => 'Addresses Congress\'s relationships with the executive and judicial branches, including the feedback loops needed to ensure laws work as intended.'],
                            ['name' => 'Global Learning', 'project_type' => 'chapter', 'lead' => 'Aubrey', 'description' => 'Draws lessons from peer parliaments around the world that have pioneered innovations Congress can adapt.'],
                            ['name' => 'Civil Society and External Engagement', 'project_type' => 'chapter', 'lead' => 'Anne', 'description' => 'Considers Congress\'s relationship with the broader ecosystem of organizations, experts, and citizens that support democratic governance.'],
                        ],
                    ],
                    [
                        'name' => 'REBOOT CONGRESS EVENTS',
                        'project_type' => 'event',
                        'status' => 'planning',
                        'children' => [
                            ['name' => 'Q1 Hill Event', 'project_type' => 'event'],
                            ['name' => 'Q2 Hill Event', 'project_type' => 'event'],
                            ['name' => 'Q3 Hill Event', 'project_type' => 'event'],
                            ['name' => 'Q4 Hill Event', 'project_type' => 'event'],
                        ],
                    ],
                ],
            ],

            // REP BODIES V2
            [
                'name' => 'REP BODIES V2',
                'project_type' => 'publication',
                'lead' => 'Marci',
                'scope' => 'Global',
                'status' => 'planning',
                'description' => 'Vol II of Rep Bodies launch',
            ],

            // CASEWORK NAVIGATOR
            [
                'name' => 'CASEWORK NAVIGATOR',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    ['name' => 'Casework Navigator Newsletter', 'project_type' => 'newsletter'],
                    [
                        'name' => 'Casework Navigator Events',
                        'project_type' => 'event',
                        'children' => [
                            ['name' => 'HDS Data Management Workshop', 'project_type' => 'event'],
                            ['name' => 'District Briefing: Full-Stack Constituent Engagement', 'project_type' => 'event'],
                        ],
                    ],
                ],
            ],

            // DEPARTURE DIALOGUES
            [
                'name' => 'DEPARTURE DIALOGUES',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    ['name' => 'DD Report', 'project_type' => 'publication'],
                ],
            ],

            // FUTUREPROOFING
            [
                'name' => 'FUTUREPROOFING',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    ['name' => 'FutureProofing Newsletter', 'project_type' => 'newsletter'],
                    ['name' => 'Hill Briefing: Future of Staffing', 'project_type' => 'event'],
                ],
            ],

            // GAVEL IN
            [
                'name' => 'GAVEL IN',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    ['name' => 'Podcast', 'project_type' => 'publication'],
                ],
            ],

            // DIGITAL PARLIAMENTS PROJECT
            [
                'name' => 'DIGITAL PARLIAMENTS PROJECT',
                'project_type' => 'initiative',
                'scope' => 'Global',
                'status' => 'active',
                'children' => [
                    [
                        'name' => 'PARLLINK',
                        'project_type' => 'tool',
                        'children' => [
                            ['name' => 'Hansard Transcription Tool', 'project_type' => 'tool'],
                        ],
                    ],
                    [
                        'name' => 'AFRICA DPP',
                        'project_type' => 'initiative',
                        'children' => [
                            ['name' => 'Uganda', 'project_type' => 'component'],
                            ['name' => 'Ghana', 'project_type' => 'component'],
                            ['name' => 'Pan-African Parliament', 'project_type' => 'component'],
                        ],
                    ],
                    [
                        'name' => 'CARIBBEAN DPP',
                        'project_type' => 'initiative',
                        'children' => [
                            ['name' => 'Bahamas', 'project_type' => 'component'],
                            ['name' => 'Jamaica', 'project_type' => 'component'],
                            ['name' => 'Belize', 'project_type' => 'component'],
                            ['name' => 'Dominica', 'project_type' => 'component'],
                            ['name' => 'Saint Lucia', 'project_type' => 'component'],
                            ['name' => 'St Vincent & Grenadines', 'project_type' => 'component'],
                            ['name' => 'Barbados', 'project_type' => 'component'],
                        ],
                    ],
                ],
            ],

            // APPROPRIATIONS
            [
                'name' => 'APPROPRIATIONS',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'active',
                'children' => [
                    ['name' => 'House Requests', 'project_type' => 'component'],
                    ['name' => 'Senate Requests', 'project_type' => 'component'],
                    ['name' => 'ApproPRO Tool', 'project_type' => 'tool'],
                ],
            ],

            // NEW MEMBER ORIENTATION
            [
                'name' => 'NEW MEMBER ORIENTATION',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'planning',
            ],

            // 120th RULES PACKAGE
            [
                'name' => '120th RULES PACKAGE',
                'project_type' => 'research',
                'scope' => 'US',
                'status' => 'planning',
            ],

            // GLOBAL PARTNERSHIPS
            [
                'name' => 'GLOBAL PARTNERSHIPS',
                'project_type' => 'initiative',
                'scope' => 'Global',
                'status' => 'active',
                'children' => [
                    ['name' => 'US/UK Fellowship WFD', 'project_type' => 'component'],
                    ['name' => 'IPU/CPA Tech Collab', 'project_type' => 'component'],
                    ['name' => 'Global Trainings', 'project_type' => 'event'],
                ],
            ],

            // STATES
            [
                'name' => 'STATES',
                'project_type' => 'initiative',
                'scope' => 'US',
                'status' => 'planning',
                'children' => [
                    ['name' => 'NCSL', 'project_type' => 'component'],
                ],
            ],

            // RECONSTITUTION
            [
                'name' => 'RECONSTITUTION',
                'project_type' => 'research',
                'scope' => 'US',
                'status' => 'planning',
            ],
        ];

        $sortOrder = 0;
        foreach ($projects as $projectData) {
            $sortOrder++;
            $this->createProjectWithChildren($projectData, null, $sortOrder);
        }
    }

    private function createProjectWithChildren(array $data, ?int $parentId, int &$sortOrder): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'] ?? 'initiative',
            'parent_project_id' => $parentId,
            'lead' => $data['lead'] ?? null,
            'scope' => $data['scope'] ?? null,
            'status' => $data['status'] ?? 'planning',
            'sort_order' => $sortOrder,
        ]);

        foreach ($children as $childData) {
            $sortOrder++;
            $this->createProjectWithChildren($childData, $project->id, $sortOrder);
        }
    }
}
