<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;

class MentionSearchController extends Controller
{
    /**
     * Search for mentionable entities (people, organizations, staff).
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, people, organizations, staff

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $results = [];

        // Search People
        if ($type === 'all' || $type === 'people') {
            $people = Person::where('name', 'like', "%{$query}%")
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => 'person',
                    'subtitle' => $p->title ?? ($p->organization?->name ?? ''),
                    'value' => "@[{$p->name}](person:{$p->id})",
                ]);
            $results = array_merge($results, $people->toArray());
        }

        // Search Organizations
        if ($type === 'all' || $type === 'organizations') {
            $orgs = Organization::where('name', 'like', "%{$query}%")
                ->orWhere('abbreviation', 'like', "%{$query}%")
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn($o) => [
                    'id' => $o->id,
                    'name' => $o->abbreviation ? "{$o->name} ({$o->abbreviation})" : $o->name,
                    'type' => 'organization',
                    'subtitle' => $o->type ?? '',
                    'value' => "@[{$o->name}](org:{$o->id})",
                ]);
            $results = array_merge($results, $orgs->toArray());
        }

        // Search Staff (Users)
        if ($type === 'all' || $type === 'staff') {
            $staff = User::where('name', 'like', "%{$query}%")
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'type' => 'staff',
                    'subtitle' => $u->is_admin ? 'Admin' : 'Staff',
                    'value' => "@[{$u->name}](staff:{$u->id})",
                ]);
            $results = array_merge($results, $staff->toArray());
        }

        return response()->json($results);
    }

    /**
     * Search organizations only.
     */
    public function searchOrganizations(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        return Organization::where('name', 'like', "%{$query}%")
            ->orWhere('abbreviation', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'subtitle' => $o->abbreviation ?? $o->type ?? '',
            ]);
    }

    /**
     * Search people only.
     */
    public function searchPeople(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        return Person::where('name', 'like', "%{$query}%")
            ->with('organization')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'subtitle' => $p->title ?? ($p->organization?->name ?? ''),
            ]);
    }

    /**
     * Search issues only.
     */
    public function searchIssues(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        return Issue::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'subtitle' => '',
            ]);
    }

    /**
     * Search staff (users) only.
     */
    public function searchStaff(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        return User::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'subtitle' => $u->is_admin ? 'Admin' : 'Staff',
            ]);
    }
}
