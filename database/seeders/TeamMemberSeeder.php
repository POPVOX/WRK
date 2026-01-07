<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teamMembers = [
            [
                'name' => 'Marci Harris, J.D., LL.M',
                'email' => 'marci@popvox.org',
                'title' => 'Cofounder & Executive Director',
                'role' => 'staff',
                'access_level' => 'admin',
                'bio' => 'Founder/CEO of POPVOX.com and Executive Director of POPVOX Foundation; lawyer and former House Ways & Means ACA staffer; fellowships at Harvard Ash Center and New America; has taught public policy/government/civic tech at USF, SJSU, and UC Berkeley.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/1621352584737-4WG9BE21ULC7USB3PVZ5/Marci%2Bheadshot.jpg',
            ],
            [
                'name' => 'Aubrey Wilson',
                'email' => 'aubrey@popvox.org',
                'title' => 'Director of Global Initiatives',
                'role' => 'staff',
                'access_level' => 'management',
                'bio' => 'Former Deputy Staff Director (118th) and Oversight/Modernization Director (117th) for the House Administration Committee; former House legislative assistant and R Street Institute governance/federal affairs team member; hosts Gavel In and edits Future-Proofing Congress.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/8148afed-0497-4663-a63a-60dc9925b520/Aubrey%2BWilson%2B2025%2Bsquare.jpg',
            ],
            [
                'name' => 'Chloe Ladd',
                'email' => 'chloe@popvox.org',
                'title' => 'Program Manager for Global Initiatives',
                'role' => 'staff',
                'access_level' => 'staff',
                'bio' => 'Supports global democratic-institution networks and innovation; previously managed Bertelsmann Foundation Fellowship and led French research portfolio; fluent in French; Georgetown SFS master\'s; UVA BA (Spanish & IR); volunteer firefighter; from France, based in Virginia.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/5dd08c1d-dff6-40e6-b4af-b6f9011a17c2/Chloe%2BLadd.jpg',
            ],
            [
                'name' => 'Gabriela Schneider',
                'email' => 'gabriela@popvox.org',
                'title' => 'Senior Communications Advisor',
                'role' => 'staff',
                'access_level' => 'staff',
                'bio' => 'Democracy reform and strategic communications leader; most recently led comms for Demand Progress\' Democracy/Open Government work; previously at Issue One and Sunlight Foundation; published in Teen Vogue/NYT/WaPo; UT Austin journalism master\'s; Lehigh journalism BA.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/7ddf1896-77fa-499e-bf80-03ed5dc4ae01/Gabriela%2BSchneider%2B2025.png',
            ],
            [
                'name' => 'Danielle Stewart',
                'email' => 'danielle@popvox.org',
                'title' => 'Advisor for Congressional Initiatives',
                'role' => 'staff',
                'access_level' => 'staff',
                'bio' => 'Capitol Hill veteran (10+ years); most recently Chief of Staff to a freshman Member; served in House and Senate offices; worked for Rep. Tom Graves as Communications Director and supported the House Select Committee on the Modernization of Congress ("ModCom") communications.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/988a2407-98b8-447f-826b-4e884c68a84e/Danielle%2BStewart.jpg',
            ],
            [
                'name' => 'Ben Harris',
                'email' => 'ben@popvox.org',
                'title' => 'Chief Operating Officer',
                'role' => 'staff',
                'access_level' => 'management',
                'bio' => 'Embry-Riddle aviation tech grad; FAA-certified mechanic and commercial pilot; "CO:founder" of theCO in Jackson, TN (cowork/maker space/code academy); Leadership Jackson alum; Boy Scout board member.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/1618092560823-HJUKN1318HWG9HWQIR56/ben-harris-popvox.png',
            ],
            [
                'name' => 'Anne Meeker',
                'email' => 'anne@popvox.org',
                'title' => 'Managing Director',
                'role' => 'staff',
                'access_level' => 'management',
                'bio' => 'Former House casework director; founding POPVOX Foundation team member; leads constituent engagement work including Casework Navigator and the Voice/Mail newsletter; former OSU institute fellow; former Director of Constituent Services for Rep. Seth Moulton; Oxford BA and LSE MSc.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/32575f65-33e7-4650-98c2-b1cf441c8a94/Anne%2BMeeker.jpg',
            ],
            [
                'name' => 'Juan García',
                'email' => 'juan@popvox.org',
                'title' => 'Creative Director',
                'role' => 'staff',
                'access_level' => 'staff',
                'bio' => 'Communications professional across nonprofit/government/private sectors; Caltech engineering & applied science BS; shifted from Silicon Valley track to Capitol Hill after a DC internship; interests include graphic design and the Oxford comma; lives in DC with husband and dog.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/f4ad65ae-4368-425d-ae19-a0da75aaf6fa/Juan%2BGarcia%2BHeadshot%2B2025.png',
            ],
            [
                'name' => 'Beatriz Rey, Ph.D.',
                'email' => 'beatriz@popvox.org',
                'title' => 'Fellow',
                'role' => 'fellow',
                'access_level' => 'staff',
                'bio' => 'Fellow and postdoctoral researcher (University of São Paulo); past visiting fellow at Johns Hopkins SNF Agora and senior researcher on the Brazilian Congress; former legislative assistant for Rep. Gregory Meeks; PhD Syracuse; MA UNC Chapel Hill (political science).',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/f1091e42-7868-47b4-bd09-8e56056918d0/Beatriz_Rey.jpg',
            ],
            [
                'name' => 'Caitlin McNally',
                'email' => 'caitlin@popvox.org',
                'title' => 'Program Associate',
                'role' => 'staff',
                'access_level' => 'staff',
                'bio' => 'Developed interest in congressional modernization through undergrad and thesis work on New Member Orientation; experience with House Subcommittee on Modernization and Rep. Derek Kilmer\'s office; Princeton (2024) Public & International Affairs; based in San Francisco; focused on tech-enabled legislative strengthening.',
                'linkedin' => null,
                'photo_url' => 'https://images.squarespace-cdn.com/content/v1/60450e1de0fb2a6f5771b1be/195ce540-3585-4e2c-b4b7-1356f4b8ca04/Caitlin%2BMcNally.jpeg',
            ],
        ];

        foreach ($teamMembers as $member) {
            User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password'),
                    'title' => $member['title'],
                    'role' => $member['role'],
                    'access_level' => $member['access_level'],
                    'bio' => $member['bio'],
                    'linkedin' => $member['linkedin'],
                    'photo_url' => $member['photo_url'],
                    'is_admin' => $member['access_level'] === 'admin',
                ]
            );
        }

        $this->command->info('Seeded '.count($teamMembers).' team members with photos.');
    }
}
