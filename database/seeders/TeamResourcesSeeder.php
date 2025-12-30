<?php

namespace Database\Seeders;

use App\Models\TeamResource;
use Illuminate\Database\Seeder;

class TeamResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            [
                'title' => 'Staff Handbook',
                'description' => 'Comprehensive guide to team policies, procedures, and culture',
                'category' => 'policy',
                'url' => null,
                'icon' => '📚',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Google Workspace',
                'description' => 'Access to Gmail, Drive, Calendar, and other Google tools',
                'category' => 'resource',
                'url' => 'https://workspace.google.com/',
                'icon' => '🔗',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Time Off Request Form',
                'description' => 'Submit requests for PTO, sick leave, and other time off',
                'category' => 'template',
                'url' => 'https://forms.google.com/',
                'icon' => '📝',
                'sort_order' => 3,
            ],
            [
                'title' => 'Expense Reimbursement',
                'description' => 'How to submit expense reports and get reimbursed',
                'category' => 'howto',
                'url' => 'https://docs.google.com/',
                'icon' => '💰',
                'sort_order' => 4,
            ],
            [
                'title' => 'Communications Guidelines',
                'description' => 'Best practices for internal and external communications',
                'category' => 'policy',
                'url' => null,
                'icon' => '📋',
                'sort_order' => 5,
            ],
            [
                'title' => 'Meeting Templates',
                'description' => 'Agenda and notes templates for various meeting types',
                'category' => 'template',
                'url' => null,
                'icon' => '📄',
                'sort_order' => 6,
            ],
        ];

        foreach ($resources as $resource) {
            TeamResource::updateOrCreate(
                ['title' => $resource['title']],
                $resource
            );
        }
    }
}
