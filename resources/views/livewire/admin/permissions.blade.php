<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Permissions</h1>
            <p class="text-gray-500 dark:text-gray-400">Manage access levels for staff.</p>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate
           class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">← Back to Dashboard</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Access Level</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Is Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rows as $index => $row)
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['email'] }}</td>
                            <td class="px-4 py-3">
                                <select wire:model="rows.{{ $index }}.access_level"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="team">Team</option>
                                    <option value="management">Management</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <select wire:model="rows.{{ $index }}.is_admin"
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

