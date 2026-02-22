<div class="max-w-[120rem] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Permissions</h1>
            <p class="text-gray-500 dark:text-gray-400">Manage platform access and agent governance rights.</p>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">← Back to Dashboard</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if(!$agentPermissionsEnabled)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
            Agent permissions table not found yet. Base user access can still be edited; run migrations to enable agent governance controls.
        </div>
    @endif

    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
        Use project IDs only when <strong>Project Scope = custom</strong>. Known projects: {{ $projectCount }}.
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Access</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Admin</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Create Specialist</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Create Project Agent</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Project Scope</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Custom Project IDs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Approve Medium</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Approve High</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rows as $index => $row)
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white whitespace-nowrap">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['email'] }}</td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.access_level"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="team">Team</option>
                                    <option value="management">Management</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[7rem]">
                                <select wire:model="rows.{{ $index }}.is_admin"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_create_specialist"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.can_create_project"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.project_scope"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="none">None</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="all">All</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[14rem]">
                                <input type="text"
                                    wire:model.defer="rows.{{ $index }}.allowed_project_ids_text"
                                    placeholder="1, 12, 48"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('rows.'.$index.'.allowed_project_ids_text')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_approve_medium_risk"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_approve_high_risk"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button wire:click="save"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                Save Changes
            </button>
        </div>
    </div>
</div>
