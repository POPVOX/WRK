<?php

namespace App\Services\Agents;

class PolicyConflictService
{
    /**
     * @param  array{
     *   org: array<string,string>,
     *   role: array<string,string>,
     *   personal: array<string,string>
     * }  $directiveSets
     * @return array<int, array{
     *   key:string,
     *   winning_layer:string,
     *   losing_layer:string,
     *   winning_value:string,
     *   losing_value:string,
     *   severity:string,
     *   code:string,
     *   message:string
     * }>
     */
    public function detectLayerConflicts(array $directiveSets): array
    {
        $org = $directiveSets['org'] ?? [];
        $role = $directiveSets['role'] ?? [];
        $personal = $directiveSets['personal'] ?? [];

        $keys = array_unique(array_merge(array_keys($org), array_keys($role), array_keys($personal)));
        sort($keys);

        $conflicts = [];

        foreach ($keys as $key) {
            $orgValue = $org[$key] ?? null;
            $roleValue = $role[$key] ?? null;
            $personalValue = $personal[$key] ?? null;

            if ($orgValue !== null) {
                if ($roleValue !== null && $roleValue !== $orgValue) {
                    $conflicts[] = [
                        'key' => $key,
                        'winning_layer' => 'org',
                        'losing_layer' => 'role',
                        'winning_value' => $orgValue,
                        'losing_value' => $roleValue,
                        'severity' => 'error',
                        'code' => 'prohibited_override',
                        'message' => "Org policy for `{$key}` cannot be overridden by role.",
                    ];
                }

                if ($personalValue !== null && $personalValue !== $orgValue) {
                    $conflicts[] = [
                        'key' => $key,
                        'winning_layer' => 'org',
                        'losing_layer' => 'personal',
                        'winning_value' => $orgValue,
                        'losing_value' => $personalValue,
                        'severity' => 'error',
                        'code' => 'prohibited_override',
                        'message' => "Org policy for `{$key}` cannot be overridden by personal instructions.",
                    ];
                }

                continue;
            }

            if ($roleValue !== null && $personalValue !== null && $roleValue !== $personalValue) {
                $conflicts[] = [
                    'key' => $key,
                    'winning_layer' => 'role',
                    'losing_layer' => 'personal',
                    'winning_value' => $roleValue,
                    'losing_value' => $personalValue,
                    'severity' => 'warning',
                    'code' => 'role_overrides_personal',
                    'message' => "Role value for `{$key}` overrides personal value.",
                ];
            }
        }

        return $conflicts;
    }

    /**
     * @param  array<int, array{
     *   severity:string,
     *   code:string,
     *   message:string,
     *   key:string
     * }>  $conflicts
     * @return array<int, array{severity:string,code:string,key:string,message:string}>
     */
    public function buildDiagnostics(array $conflicts): array
    {
        return array_values(array_map(static function (array $conflict): array {
            return [
                'severity' => $conflict['severity'],
                'code' => $conflict['code'],
                'key' => $conflict['key'],
                'message' => $conflict['message'],
            ];
        }, $conflicts));
    }
}
