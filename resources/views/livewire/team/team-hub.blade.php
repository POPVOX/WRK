<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Team Hub</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Your central space for team info, resources, and
                communication</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">

            <button wire:click="setTab('team')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'team' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Team Members
            </button>
            <button wire:click="setTab('messages')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'messages' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Message Board
            </button>
            <button wire:click="setTab('resources')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'resources' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Resources
            </button>
            <button wire:click="setTab('handbook')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'handbook' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                Ask Handbook
            </button>
        </nav>
    </div>

    <!-- Team Members Tab -->
    @if($activeTab === 'team')
        @if(auth()->user()?->isAdmin())
            <div class="mb-6 flex justify-end">
                <button wire:click="$set('showAddMemberForm', true)"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Add Team Member
                </button>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($teamMembers as $member)
                <a href="{{ route('team.member.profile', $member) }}" wire:navigate
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden hover:shadow-lg hover:ring-2 hover:ring-indigo-500/50 transition-all cursor-pointer">
                    <div class="h-20 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                    <div class="px-6 pb-6">
                        <div class="-mt-10 mb-4">
                            @if($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}"
                                    class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow-lg">
                            @else
                                <div
                                    class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center text-white text-2xl font-bold border-4 border-white dark:border-gray-800 shadow-lg">
                                    {{ substr($member->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $member->name }}</h3>
                        @if($member->title)
                            <p class="text-indigo-600 dark:text-indigo-400 text-sm font-medium">{{ $member->title }}</p>
                        @endif
                        <div class="mt-3 space-y-1">
                            @if($member->location)
                                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{{ $member->location }}</span>
                                </div>
                            @endif
                            @if($member->timezone)
                                @php
                                    try {
                                        $tz = new DateTimeZone($member->timezone);
                                        $localTime = (new DateTime('now', $tz))->format('g:i A');
                                    } catch (Exception $e) {
                                        $localTime = null;
                                    }
                                @endphp
                                @if($localTime)
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $localTime }} local</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                    No team members found.
                </div>
            @endforelse
        </div>

        {{-- Add Team Member Modal --}}
        @if($showAddMemberForm)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Team Member</h3>
                    <form wire:submit="saveMember" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input type="text" wire:model="memberName"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Full name">
                            @error('memberName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" wire:model="memberEmail"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="email@example.com">
                            @error('memberEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                            <input type="text" wire:model="memberTitle"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Job title">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                            <select wire:model="memberRole"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="staff">Staff</option>
                                <option value="management">Management</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="resetMemberForm"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                                Add Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Team Map --}}
        @if(count($teamMapData) > 0)
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Team Around the World
                    </h3>
                </div>
                <div id="team-map" class="h-96 w-full" wire:ignore></div>
            </div>
        @endif
    @endif

    <!-- Message Board Tab -->
    @if($activeTab === 'messages')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- New Message Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Post a Message</h3>
                    <form wire:submit="postMessage">
                        <textarea wire:model="newMessage" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                            placeholder="Share an update, question, or announcement with the team..."></textarea>
                        @error('newMessage') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror

                        {{-- Screenshot Upload --}}
                        <div class="mt-3 flex items-center gap-4">
                            <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Add Screenshot
                                <input type="file" wire:model="messageScreenshot" class="hidden" accept="image/*">
                            </label>
                            @if($messageScreenshot)
                                <div class="flex items-center gap-2">
                                    <img src="{{ $messageScreenshot->temporaryUrl() }}" class="h-10 w-10 object-cover rounded">
                                    <button type="button" wire:click="$set('messageScreenshot', null)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                            @error('messageScreenshot') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end mt-3">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Post Message
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Messages List -->
                <div class="space-y-4">
                    @forelse($messages as $message)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-5 {{ $message->is_pinned ? 'ring-2 ring-amber-400' : '' }}">
                            {{-- Message Header --}}
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-medium">
                                        {{ substr($message->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $message->user->name ?? 'Unknown User' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($message->is_pinned)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 dark:bg-amber-900/50 dark:text-amber-300 rounded-full">
                                            📌 Pinned
                                        </span>
                                    @endif
                                    <button wire:click="togglePin({{ $message->id }})"
                                        class="p-1 text-gray-400 hover:text-amber-500 transition-colors"
                                        title="{{ $message->is_pinned ? 'Unpin' : 'Pin' }}">
                                        <svg class="w-5 h-5" fill="{{ $message->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                        </svg>
                                    </button>
                                    @if(auth()->id() === $message->user_id || auth()->user()->isAdmin())
                                        <button wire:click="deleteMessage({{ $message->id }})" wire:confirm="Delete this message?"
                                            class="p-1 text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Message Content --}}
                            <p class="mt-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $message->content }}</p>

                            {{-- Screenshot --}}
                            @if($message->screenshot_path)
                                <div class="mt-3">
                                    <img src="{{ Storage::url($message->screenshot_path) }}" alt="Screenshot"
                                        class="max-w-full max-h-80 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90"
                                        onclick="window.open('{{ Storage::url($message->screenshot_path) }}', '_blank')">
                                </div>
                            @endif

                            {{-- Reactions --}}
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                {{-- Existing Reactions --}}
                                @foreach($message->groupedReactions as $reaction)
                                    <button wire:click="toggleReaction({{ $message->id }}, '{{ $reaction['emoji'] }}')"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded-full transition
                                            {{ in_array(auth()->id(), $reaction['user_ids']) 
                                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-300 dark:ring-indigo-700' 
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                                        title="{{ implode(', ', $reaction['users']) }}">
                                        <span>{{ $reaction['emoji'] }}</span>
                                        <span class="text-xs font-medium">{{ $reaction['count'] }}</span>
                                    </button>
                                @endforeach

                                {{-- Add Reaction Dropdown --}}
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" type="button"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-transition
                                        class="absolute left-0 mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-2 flex gap-1 z-10">
                                        @foreach($availableReactions as $emoji)
                                            <button wire:click="toggleReaction({{ $message->id }}, '{{ $emoji }}')" @click="open = false"
                                                class="text-xl hover:scale-125 transition-transform p-1">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Reply Button --}}
                                <button wire:click="startReply({{ $message->id }})"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition ml-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    Reply
                                    @if($message->replies->count() > 0)
                                        <span class="text-xs">({{ $message->replies->count() }})</span>
                                    @endif
                                </button>
                            </div>

                            {{-- Replies --}}
                            @if($message->replies->count() > 0)
                                <div class="mt-4 ml-6 pl-4 border-l-2 border-gray-200 dark:border-gray-700 space-y-3">
                                    @foreach($message->replies as $reply)
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-medium">
                                                        {{ substr($reply->user->name ?? 'U', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $reply->user->name ?? 'Unknown' }}</span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                </div>
                                                @if(auth()->id() === $reply->user_id || auth()->user()->isAdmin())
                                                    <button wire:click="deleteMessage({{ $reply->id }})" wire:confirm="Delete this reply?"
                                                        class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reply->content }}</p>
                                            @if($reply->screenshot_path)
                                                <img src="{{ Storage::url($reply->screenshot_path) }}" alt="Screenshot"
                                                    class="mt-2 max-w-xs max-h-40 rounded border border-gray-200 dark:border-gray-600 cursor-pointer"
                                                    onclick="window.open('{{ Storage::url($reply->screenshot_path) }}', '_blank')">
                                            @endif

                                            {{-- Reply Reactions --}}
                                            <div class="mt-2 flex flex-wrap items-center gap-1">
                                                @foreach($reply->groupedReactions as $reaction)
                                                    <button wire:click="toggleReaction({{ $reply->id }}, '{{ $reaction['emoji'] }}')"
                                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-xs rounded-full transition
                                                            {{ in_array(auth()->id(), $reaction['user_ids']) 
                                                                ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' 
                                                                : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' }}">
                                                        <span>{{ $reaction['emoji'] }}</span>
                                                        <span>{{ $reaction['count'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Reply Form --}}
                            @if($replyingTo === $message->id)
                                <div class="mt-4 ml-6 pl-4 border-l-2 border-indigo-300 dark:border-indigo-700">
                                    <form wire:submit="postReply" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                        <textarea wire:model="replyContent" rows="2"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                            placeholder="Write a reply..."></textarea>
                                        @error('replyContent') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                                        <div class="mt-2 flex items-center justify-between">
                                            <label class="flex items-center gap-1 px-2 py-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-600 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-500 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Screenshot
                                                <input type="file" wire:model="replyScreenshot" class="hidden" accept="image/*">
                                            </label>
                                            @if($replyScreenshot)
                                                <img src="{{ $replyScreenshot->temporaryUrl() }}" class="h-8 w-8 object-cover rounded">
                                            @endif
                                            <div class="flex gap-2">
                                                <button type="button" wire:click="cancelReply"
                                                    class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="px-3 py-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded transition">
                                                    Reply
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No messages yet</h3>
                            <p class="text-gray-500 dark:text-gray-400">Be the first to share something with the team!</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($messages->hasPages())
                    <div class="mt-6">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>

            <!-- Pinned Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 sticky top-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        Pinned Messages
                    </h3>
                    @forelse($pinnedMessages as $pinned)
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg mb-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $pinned->user->name ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-3">{{ $pinned->content }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $pinned->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No pinned messages.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- Resources Tab -->
    @if($activeTab === 'resources')
        <div class="mb-6 flex justify-end">
            <button wire:click="$set('showResourceForm', true)"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Resource
            </button>
        </div>

        <!-- Add Resource Modal -->
        @if($showResourceForm)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $editingResourceId ? 'Edit Resource' : 'Add New Resource' }}
                    </h3>
                    <form wire:submit="saveResource" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                            <select wire:model="resourceIcon"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="📄">📄 Document</option>
                                <option value="📋">📋 Policy</option>
                                <option value="📚">📚 Guide</option>
                                <option value="🔗">🔗 Link</option>
                                <option value="📁">📁 Folder</option>
                                <option value="📝">📝 Template</option>
                                <option value="🎯">🎯 Goals</option>
                                <option value="📊">📊 Report</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                            <input type="text" wire:model="resourceTitle"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Resource title">
                            @error('resourceTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select wire:model="resourceCategory"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                @foreach($resourceCategories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                            <input type="url" wire:model="resourceUrl"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="https://...">
                            @error('resourceUrl') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea wire:model="resourceDescription" rows="2"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white resize-none"
                                placeholder="Brief description..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="resetResourceForm"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                                Save Resource
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Resources Grid -->
        <div class="space-y-8">
            @forelse($resources as $category => $categoryResources)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $resourceCategories[$category] ?? ucfirst($category) }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($categoryResources as $resource)
                            <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-md p-5 hover:shadow-lg hover:ring-2 hover:ring-indigo-500/50 transition-all relative">
                                <a href="{{ $resource->url }}" target="_blank" rel="noopener" class="block">
                                    <div class="flex items-start">
                                        <span class="text-3xl mr-4">{{ $resource->icon ?? '📄' }}</span>
                                        <div class="flex-1 min-w-0">
                                            <h4
                                                class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ $resource->title }}</h4>
                                            @if($resource->description)
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                                    {{ $resource->description }}</p>
                                            @endif
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors flex-shrink-0"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </div>
                                </a>
                                @if(auth()->user()?->isAdmin())
                                    <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button wire:click="moveResourceUp({{ $resource->id }})"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                            title="Move Up">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </button>
                                        <button wire:click="moveResourceDown({{ $resource->id }})"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                            title="Move Down">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                        <button wire:click="editResource({{ $resource->id }})"
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="deleteResource({{ $resource->id }})"
                                            wire:confirm="Are you sure you want to delete this resource?"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
                    <div
                        class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No resources yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Add your first resource link or policy document.</p>
                    <button wire:click="$set('showResourceForm', true)"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Resource
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    <!-- Handbook AI Tab -->
    @if($activeTab === 'handbook')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Query Input -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ask the Handbook</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Get instant answers from the staff handbook</p>
                    </div>
                </div>

                <form wire:submit="queryHandbook" class="space-y-4">
                    <div>
                        <textarea wire:model="aiQuery" rows="4"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                            placeholder="Ask a question about team policies, procedures, culture, or work approaches. E.g., 'What is the team's approach to remote work?' or 'How should I handle a meeting conflict?'"
                            @if($isQuerying) disabled @endif></textarea>
                        @error('aiQuery') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-between">
                        <button type="button" wire:click="clearChat"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Clear conversation
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            @if($isQuerying) disabled @endif>
                            @if($isQuerying)
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Thinking...
                            @else
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Ask Question
                            @endif
                        </button>
                    </div>
                </form>

                <!-- Example Questions -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Try asking:</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="$set('aiQuery', 'What is the team mission?')"
                            class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Team mission
                        </button>
                        <button type="button" wire:click="$set('aiQuery', 'How should I handle communications?')"
                            class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Communications
                        </button>
                        <button type="button" wire:click="$set('aiQuery', 'What are the work approaches?')"
                            class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Work approaches
                        </button>
                        <button type="button" wire:click="$set('aiQuery', 'What is the policy on partisanship?')"
                            class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Partisanship policy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Response Area -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    Response
                </h3>

                @if($aiResponse)
                    @if($lastQuestion)
                    <div class="mb-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border-l-4 border-indigo-500">
                        <p class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-1">Your question:</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $lastQuestion }}</p>
                    </div>
                    @endif
                    <div
                        class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-5 min-h-[200px]">
                        <p class="text-xs font-medium text-purple-600 dark:text-purple-400 mb-2">Handbook AI Response:</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($aiResponse)) !!}
                        </div>
                    </div>
                @elseif(count($chatHistory) > 0)
                    <div class="space-y-4 max-h-[400px] overflow-y-auto">
                        @foreach($chatHistory as $chat)
                            <div class="@if($chat['role'] === 'user') ml-8 @else mr-8 @endif">
                                <div
                                    class="@if($chat['role'] === 'user') bg-indigo-100 dark:bg-indigo-900/30 @else bg-purple-50 dark:bg-purple-900/20 @endif rounded-lg p-4">
                                    <p
                                        class="text-xs font-medium @if($chat['role'] === 'user') text-indigo-600 dark:text-indigo-400 @else text-purple-600 dark:text-purple-400 @endif mb-1">
                                        {{ $chat['role'] === 'user' ? 'You' : 'Handbook AI' }}
                                    </p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $chat['content'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-[200px] text-center">
                        <div
                            class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h4 class="text-gray-900 dark:text-white font-medium mb-1">Ready to help!</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ask a question about the staff handbook to get
                            started.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@if($activeTab === 'team' && isset($teamMapData) && count($teamMapData) > 0)
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .team-marker {
        background: white;
        border-radius: 50%;
        border: 3px solid #6366f1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        overflow: hidden;
    }
    .team-marker img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .team-marker-initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6366f1, #a855f7);
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const teamMapData = @json($teamMapData);

    document.addEventListener('DOMContentLoaded', function() {
        initTeamMap();
    });

    document.addEventListener('livewire:navigated', function() {
        setTimeout(initTeamMap, 100);
    });

    function initTeamMap() {
        const mapContainer = document.getElementById('team-map');
        if (!mapContainer || mapContainer._leaflet_id) return;

        if (!teamMapData.length) return;

        // Calculate center based on team locations
        const lats = teamMapData.map(m => m.lat);
        const lngs = teamMapData.map(m => m.lng);
        const centerLat = (Math.min(...lats) + Math.max(...lats)) / 2;
        const centerLng = (Math.min(...lngs) + Math.max(...lngs)) / 2;

        // World bounds to prevent repeating
        const worldBounds = L.latLngBounds(
            L.latLng(-85, -180),
            L.latLng(85, 180)
        );

        const map = L.map('team-map', {
            maxBounds: worldBounds,
            maxBoundsViscosity: 1.0,
            minZoom: 2
        }).setView([centerLat, centerLng], 4);

        // Use a nice map style
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19,
            noWrap: true,
            bounds: worldBounds
        }).addTo(map);

        // Add markers for each team member
        teamMapData.forEach(function(member) {
            const markerHtml = member.photo_url
                ? `<div class="team-marker" style="width:40px;height:40px;"><img src="${member.photo_url}" alt="${member.name}"></div>`
                : `<div class="team-marker" style="width:40px;height:40px;"><div class="team-marker-initials">${member.name.charAt(0)}</div></div>`;

            const icon = L.divIcon({
                html: markerHtml,
                className: '',
                iconSize: [46, 46],
                iconAnchor: [23, 23],
                popupAnchor: [0, -23]
            });

            const marker = L.marker([member.lat, member.lng], { icon: icon }).addTo(map);

            marker.bindPopup(`
                <div class="text-center p-2">
                    <strong class="text-gray-900">${member.name}</strong><br>
                    <span class="text-indigo-600 text-sm">${member.title || ''}</span><br>
                    <span class="text-gray-500 text-xs">${member.location}</span>
                </div>
            `);
        });

        // Fit bounds to show all markers
        if (teamMapData.length > 1) {
            const bounds = L.latLngBounds(teamMapData.map(m => [m.lat, m.lng]));
            map.fitBounds(bounds, { padding: [50, 50] });
        }

        // Fix tile rendering issues by invalidating size after a short delay
        setTimeout(function() {
            map.invalidateSize();
        }, 100);
    }
</script>
@endpush
@endif