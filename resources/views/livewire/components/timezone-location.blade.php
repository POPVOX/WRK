<div>
    {{-- Inline display for dashboard --}}
    @if(!$isPrompt)
        <button wire:click="openModal"
            class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            @if($timezone)
                <span>{{ $location ? $location . ' · ' : '' }}{{ $timezones[$timezone] ?? $timezone }}</span>
            @else
                <span>Set timezone</span>
            @endif
        </button>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
            x-data="{ 
                detectTimezone() {
                    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    $wire.setDetectedTimezone(tz);
                }
            }"
            x-init="detectTimezone()">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$wire.closeModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    @if($isPrompt)
                                        Where are you today?
                                    @else
                                        Update Timezone & Location
                                    @endif
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($isPrompt)
                                        Help us show times correctly for your current location
                                    @else
                                        Keep this updated when you travel
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($detectedTimezone && $detectedTimezone !== $timezone)
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-blue-700 dark:text-blue-300">
                                        <span class="font-medium">Detected:</span> {{ $timezones[$detectedTimezone] ?? $detectedTimezone }}
                                    </div>
                                    <button wire:click="useDetected" type="button"
                                        class="text-xs font-medium text-blue-700 dark:text-blue-300 hover:underline">
                                        Use this
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Timezone <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="timezone" id="timezone"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select timezone...</option>
                                    @foreach($timezones as $tz => $label)
                                        <option value="{{ $tz }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('timezone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Current Location <span class="text-gray-400">(optional)</span>
                                </label>
                                <input type="text" wire:model="location" id="location"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="e.g., Washington, DC or London, UK">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Helpful for teammates to know where you are</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button" wire:click="save"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        @if($isPrompt)
                            <button type="button" wire:click="skipPrompt"
                                class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 sm:w-auto sm:text-sm">
                                Skip for now
                            </button>
                        @else
                            <button type="button" wire:click="closeModal"
                                class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

