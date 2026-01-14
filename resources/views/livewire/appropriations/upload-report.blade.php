<div>
    <x-slot name="header">
        <div class="flex items-center">
            <button wire:click="goBack" class="mr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $step === 1 ? 'Add Appropriations Report' : ($step === 2 ? 'Review Requirements' : 'Confirm Import') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Progress Steps --}}
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500' }}">Upload</span>
                    </div>
                    <div class="w-16 h-0.5 mx-4 {{ $step >= 2 ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-500' }}">Review</span>
                    </div>
                    <div class="w-16 h-0.5 mx-4 {{ $step >= 3 ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $step >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-500' }}">Confirm</span>
                    </div>
                </div>
            </div>

            {{-- Step 1: Upload --}}
            @if($step === 1)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Report Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiscal Year *</label>
                            <select wire:model="fiscalYear" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @for($y = date('Y') + 2; $y >= date('Y') - 2; $y--)
                                    <option value="FY{{ $y }}">FY{{ $y }}</option>
                                @endfor
                            </select>
                            @error('fiscalYear') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Report Type *</label>
                            <select wire:model="reportType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="house">House</option>
                                <option value="senate">Senate</option>
                            </select>
                            @error('reportType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Report Number *</label>
                            <input type="text" wire:model="reportNumber" placeholder="e.g., 119-178"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('reportNumber') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enactment Date</label>
                            <input type="date" wire:model="enactmentDate"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('enactmentDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                            <input type="text" wire:model="title" placeholder="Legislative Branch Appropriations Act, 2026"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea wire:model="notes" rows="2"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Optional notes about this report..."></textarea>
                        </div>
                    </div>

                    <hr class="my-6 border-gray-200 dark:border-gray-700">

                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Upload PDF for AI Extraction (Optional)</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Report PDF</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    @if($reportFile)
                                        <p class="text-sm text-green-600 dark:text-green-400 font-medium">{{ $reportFile->getClientOriginalName() }}</p>
                                    @else
                                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">PDF up to 50MB</p>
                                    @endif
                                </div>
                                <input type="file" wire:model="reportFile" class="hidden" accept=".pdf">
                            </label>
                        </div>
                        @error('reportFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <button type="button" wire:click="skipPdfUpload"
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 text-sm">
                            Skip PDF upload, add requirements manually →
                        </button>

                        <button type="button" wire:click="processReport"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            wire:loading.attr="disabled"
                            {{ $reportFile ? '' : 'disabled' }}>
                            <span wire:loading.remove wire:target="processReport">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                Process with AI
                            </span>
                            <span wire:loading wire:target="processReport">
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Step 2: Review --}}
            @if($step === 2)
                <div class="space-y-6">
                    {{-- Report Summary --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ ucfirst($reportType) }} Report {{ $reportNumber }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-indigo-600">{{ count($extractedRequirements) }}</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Requirements</p>
                            </div>
                        </div>
                    </div>

                    {{-- Add More Requirements --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-gray-900 dark:text-white">Add More Requirements</h3>
                            <button wire:click="$toggle('showManualEntry')"
                                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                {{ $showManualEntry ? 'Cancel' : '+ Add Manually' }}
                            </button>
                        </div>

                        {{-- Paste Text for AI --}}
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paste text for AI extraction</label>
                            <textarea wire:model="pasteText" rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="Paste report text here to extract additional requirements..."></textarea>
                            <button wire:click="parseFromText"
                                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="parseFromText">Extract from text</span>
                                <span wire:loading wire:target="parseFromText">Extracting...</span>
                            </button>
                        </div>

                        {{-- Manual Entry Form --}}
                        @if($showManualEntry)
                            <div class="border-t dark:border-gray-700 pt-4 mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                                        <input type="text" wire:model="manualTitle"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('manualTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agency *</label>
                                        <select wire:model="manualAgency"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Select agency...</option>
                                            @foreach($agencies as $abbr => $name)
                                                <option value="{{ $name }}">{{ $name }} ({{ $abbr }})</option>
                                            @endforeach
                                        </select>
                                        @error('manualAgency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                                        <select wire:model="manualCategory"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timeline Type *</label>
                                        <select wire:model="manualTimelineType"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach($timelineTypes as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Days (if applicable)</label>
                                        <input type="number" wire:model="manualTimelineValue" min="1"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                                        <textarea wire:model="manualDescription" rows="2"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        @error('manualDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recipients *</label>
                                        <input type="text" wire:model="manualRecipients" placeholder="House and Senate Committees on Appropriations"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        @error('manualRecipients') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Page Reference</label>
                                        <input type="text" wire:model="manualPageRef" placeholder="p. 15"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button wire:click="addManualRequirement"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                        Add Requirement
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Requirements List --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                            <h3 class="font-medium text-gray-900 dark:text-white">Extracted Requirements</h3>
                        </div>

                        @if(count($extractedRequirements) === 0)
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p>No requirements yet. Add them manually or paste text above.</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($extractedRequirements as $index => $req)
                                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="req-{{ $index }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $req['title'] }}</h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($req['description'], 150) }}</p>
                                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-600 rounded">{{ $req['agency'] }}</span>
                                                    <span>{{ ucfirst(str_replace('_', ' ', $req['category'] ?? 'new')) }}</span>
                                                    @if(isset($req['timeline_value']))
                                                        <span>{{ $req['timeline_value'] }} days</span>
                                                    @endif
                                                    @if(isset($req['page_reference']))
                                                        <span>{{ $req['page_reference'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <button wire:click="removeRequirement({{ $index }})"
                                                class="ml-4 text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                title="Remove">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between">
                        <button wire:click="goBack" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            ← Back
                        </button>
                        <button wire:click="confirmAndSave"
                            class="inline-flex items-center px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700"
                            {{ count($extractedRequirements) === 0 ? 'disabled' : '' }}>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Import {{ count($extractedRequirements) }} Requirements
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

