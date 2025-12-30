<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Active Relationships
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Most engaged this quarter</p>
    </div>

    <div class="p-5">
        @if($activeRelationships->isEmpty())
            <div class="text-center py-6">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No relationship activity yet</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($activeRelationships as $org)
                    @php
                        $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                        $colorIndex = abs(crc32($org->name ?? 'X')) % count($colors);
                    @endphp
                    <a href="{{ route('organizations.show', $org) }}"
                        class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition group">
                        <div class="flex items-center gap-3">
                            {{-- Org Avatar --}}
                            <div
                                class="w-10 h-10 rounded-lg {{ $colors[$colorIndex] }} flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($org->name ?? 'O', 0, 2)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div
                                    class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition truncate">
                                    {{ $org->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $org->meetings_count }} meeting{{ $org->meetings_count > 1 ? 's' : '' }}
                                    @if($org->commitments_count > 0)
                                        • {{ $org->commitments_count }} open commitment{{ $org->commitments_count > 1 ? 's' : '' }}
                                    @endif
                                </div>
                            </div>

                            {{-- Activity Indicator --}}
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <span
                                        class="w-2 h-2 rounded-full {{ $i <= min($org->meetings_count, 5) ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600' }}"></span>
                                @endfor
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
        <a href="{{ route('organizations.index') }}"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
            View all organizations →
        </a>
    </div>
</div>