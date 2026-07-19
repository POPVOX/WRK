@php
    $cleanMeetingLink = trim((string) preg_split('/<br\s*\/?\s*>|\s+/i', (string) $meeting->meeting_link)[0]);
    $cleanNotes = strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], (string) $meeting->raw_notes));
    $purposeLabel = \App\Models\Meeting::TYPE_LABELS[$meetingType] ?? \App\Models\Meeting::TYPE_LABELS[\App\Models\Meeting::TYPE_STAKEHOLDER];
@endphp

<div class="desk-page meeting-detail-page">
    <header class="meeting-detail-header">
        <div>
            <a href="{{ route('meetings.index') }}" wire:navigate class="desk-kicker">Meetings · {{ $meeting->meeting_date->format('M j, Y') }}</a>
            <h1 class="desk-page-title">{{ $meeting->title ?: 'Untitled meeting' }}</h1>
            <p class="desk-meta">
                {{ $meeting->meeting_date->format('l, F j, Y') }}
                @if($meeting->meeting_time) · {{ \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') }}@endif
                @if($meeting->location) · {{ Str::limit(strip_tags($meeting->location), 70) }}@endif
                · logged by {{ $meeting->user?->name ?: 'WRK' }}
            </p>
        </div>
        <div class="desk-toolbar meeting-detail-toolbar">
            @if(filter_var($cleanMeetingLink, FILTER_VALIDATE_URL))
                <a href="{{ $cleanMeetingLink }}" target="_blank" rel="noopener" class="desk-button-secondary">Join meeting ↗</a>
            @endif
            <select wire:change="updateStatus($event.target.value)" aria-label="Meeting status">
                @foreach(\App\Models\Meeting::STATUSES as $status)
                    <option value="{{ $status }}" {{ $meeting->status === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="openPrepModal" class="desk-button-dark">✦ Prep brief</button>
            @if($editing)
                <button type="button" wire:click="save" class="desk-button-primary">Save changes</button>
                <button type="button" wire:click="cancelEditing" class="desk-button-secondary">Cancel</button>
            @else
                <button type="button" wire:click="startEditing" class="desk-button-secondary">Edit record</button>
                <button type="button" wire:click="deleteMeeting" wire:confirm="Are you sure you want to delete this meeting?" class="meeting-danger-link">Delete</button>
            @endif
        </div>
    </header>

    @if($editing)
        <section class="meeting-edit-panel">
            <div class="meeting-section-heading">
                <div><p class="desk-kicker">Edit the durable record</p><h2>Meeting details</h2></div>
                <span class="meeting-source-badge">Human edited</span>
            </div>
            <div class="meeting-edit-grid">
                <label class="meeting-field meeting-edit-wide"><span>Title</span><input type="text" wire:model="title"></label>
                <label class="meeting-field"><span>Date</span><input type="date" wire:model="meeting_date"></label>
                <label class="meeting-field"><span>Start time</span><input type="time" wire:model="meeting_time"></label>
                <label class="meeting-field"><span>End time</span><input type="time" wire:model="meeting_end_time"></label>
                <label class="meeting-field"><span>Purpose</span><select wire:model="meetingType">@foreach(\App\Models\Meeting::TYPE_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="meeting-field meeting-edit-wide"><span>What should WRK notice?</span><textarea wire:model="aiFocus" rows="2"></textarea></label>
                <label class="meeting-field"><span>Organizations</span><select wire:model="selectedOrganizations" multiple size="5">@foreach($allOrganizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></label>
                <label class="meeting-field"><span>People</span><select wire:model="selectedPeople" multiple size="5">@foreach($allPeople as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></label>
                <label class="meeting-field"><span>Topics</span><select wire:model="selectedIssues" multiple size="5">@foreach($allIssues as $issue)<option value="{{ $issue->id }}">{{ $issue->name }}</option>@endforeach</select></label>
            </div>
        </section>
    @endif

    <div class="meeting-detail-grid">
        <main class="meeting-detail-main">
            <section class="meeting-document-section meeting-followups-section">
                <div class="meeting-section-heading">
                    <div><p class="desk-kicker">Follow-through</p><h2>Actions</h2></div>
                    <span class="meeting-count-badge">{{ $meeting->actions->where('status', \App\Models\Action::STATUS_PENDING)->count() }} open</span>
                </div>

                <div class="meeting-task-list">
                    @forelse($meeting->actions as $action)
                        <div class="meeting-task-row @if($action->status === \App\Models\Action::STATUS_COMPLETE) is-complete @endif">
                            <button type="button" wire:click="toggleActionComplete({{ $action->id }})" class="meeting-task-check" aria-label="Toggle task">{{ $action->status === \App\Models\Action::STATUS_COMPLETE ? '✓' : '' }}</button>
                            <div>
                                <strong>{{ $action->description }}</strong>
                                <span>
                                    {{ $action->assignedTo?->name ?: 'Unassigned' }}
                                    @if($action->due_date) · due {{ $action->due_date->format('M j') }}@endif
                                    @if($action->source === \App\Models\Action::SOURCE_AI_SUGGESTED) · ✦ accepted AI suggestion @endif
                                </span>
                            </div>
                            <button type="button" wire:click="deleteAction({{ $action->id }})" wire:confirm="Remove this action?" class="meeting-row-remove">Remove</button>
                        </div>
                    @empty
                        <p class="meeting-empty-copy">No follow-ups yet. Add one below or refresh the AI recap for suggestions.</p>
                    @endforelse
                </div>

                <form wire:submit="addAction" class="meeting-action-form">
                    <input type="text" wire:model="newActionDescription" placeholder="Add a concrete follow-up…">
                    <input type="date" wire:model="newActionDueDate" aria-label="Due date">
                    <select wire:model="newActionPriority" aria-label="Priority"><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select>
                    <button type="submit" class="desk-button-primary">Add action</button>
                </form>
            </section>

            <section class="meeting-document-section meeting-outcomes-grid">
                <div>
                    <p class="desk-kicker">Key ask</p>
                    @if($editing)<textarea wire:model="keyAsk" rows="4"></textarea>@else<p>{{ $meeting->key_ask ?: 'No clear ask recorded.' }}</p>@endif
                </div>
                <div>
                    <p class="desk-kicker">Commitments and next steps</p>
                    @if($editing)<textarea wire:model="commitmentsMade" rows="4"></textarea>@else<p>{{ $meeting->commitments_made ?: 'No commitments recorded.' }}</p>@endif
                </div>
            </section>

            <section class="meeting-document-section">
                <div class="meeting-section-heading">
                    <div>
                        <p class="desk-kicker">Source record</p>
                        <h2>Your notes</h2>
                        <p>These are the original working notes. AI refreshes never replace them.</p>
                    </div>
                    <span class="meeting-source-badge">Source of truth</span>
                </div>
                @if($editing)
                    <textarea wire:model="raw_notes" rows="18" class="meeting-notes-editor"></textarea>
                @elseif(trim($cleanNotes) !== '')
                    <div class="meeting-prose">{!! Str::markdown($cleanNotes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                @else
                    <p class="meeting-empty-copy">No notes have been added yet. Choose “Edit record” to add them.</p>
                @endif

                <div class="meeting-refresh-ai">
                    <div>
                        <strong>Refresh the AI draft from the notes above</strong>
                        <span>Uses “{{ $purposeLabel }}”{{ $aiFocus ? ' and your custom focus' : '' }}. Existing tasks are not changed.</span>
                    </div>
                    <button type="button" wire:click="summarizeNotes" wire:loading.attr="disabled" class="desk-button-dark">
                        <span wire:loading.remove wire:target="summarizeNotes">✦ Refresh AI recap</span>
                        <span wire:loading wire:target="summarizeNotes">Refreshing…</span>
                    </button>
                </div>
            </section>

            @if($meeting->ai_summary || $aiSummary || count($suggestedActions))
                <section class="meeting-document-section meeting-ai-review">
                    <div class="meeting-section-heading">
                        <div>
                            <p class="desk-kicker">AI recap</p>
                            <h2>A working interpretation</h2>
                            <p>Review against the source notes. Accepted follow-ups are visibly logged as AI-suggested tasks.</p>
                        </div>
                        <span class="meeting-ai-badge">✦ AI draft</span>
                    </div>
                    @if($editing)
                        <textarea wire:model="aiSummary" rows="7"></textarea>
                    @else
                        <div class="meeting-prose"><p>{{ $meeting->ai_summary }}</p></div>
                    @endif
                    @if($meeting->ai_generated_at)
                        <p class="meeting-ai-provenance">Generated {{ $meeting->ai_generated_at->format('M j, Y \\a\\t g:i A') }} from this meeting’s notes · {{ $purposeLabel }}</p>
                    @endif

                    @if(count($suggestedActions))
                        <div class="meeting-suggestion-group">
                            <div class="meeting-suggestion-header">
                                <div><strong>Proposed follow-ups</strong><span>Accepting creates a task; rejecting only dismisses this draft.</span></div>
                                <button type="button" wire:click="acceptAllSuggestedActions" class="desk-link">Accept all</button>
                            </div>
                            @foreach($suggestedActions as $action)
                                @php($actionKey = $this->actionSuggestionKey($action))
                                <div class="meeting-action-proposal" wire:key="detail-suggested-action-{{ $actionKey }}">
                                    <div>
                                        <strong>{{ $action['description'] }}</strong>
                                        @if($action['owner_name'] ?? null)<span>Owner mentioned: {{ $action['owner_name'] }}</span>@endif
                                        @if($action['due_date'] ?? null)<span>Due {{ $action['due_date'] }}</span>@endif
                                    </div>
                                    <div>
                                        <button type="button" wire:click="acceptSuggestedAction('{{ $actionKey }}')" class="meeting-accept-button">✓ Accept</button>
                                        <button type="button" wire:click="rejectSuggestedAction('{{ $actionKey }}')" class="meeting-reject-button">×</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            <details class="meeting-detail-disclosure" @if($meeting->prep_notes) open @endif>
                <summary><span><small>Before the meeting</small>Preparation notes</span><span>+</span></summary>
                <div>
                    @if($editing)<textarea wire:model="prep_notes" rows="8"></textarea>
                    @elseif($meeting->prep_notes)<div class="meeting-prose">{!! Str::markdown($meeting->prep_notes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                    @else<p class="meeting-empty-copy">No preparation notes yet.</p>@endif
                </div>
            </details>

            <details class="meeting-detail-disclosure" @if($meeting->agendaItems->count() || $meeting->agenda_notes) open @endif>
                <summary><span><small>Structure and decisions</small>Agenda</span><span>+</span></summary>
                <div class="meeting-agenda-content">
                    @foreach($meeting->agendaItems as $item)
                        <div class="meeting-agenda-row">
                            <select wire:change="updateAgendaItemStatus({{ $item->id }}, $event.target.value)">@foreach($agendaStatuses as $status)<option value="{{ $status }}" {{ $item->status === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>@endforeach</select>
                            <div><strong>{{ $item->title }}</strong>@if($item->description)<span>{{ $item->description }}</span>@endif</div>
                            <button type="button" wire:click="deleteAgendaItem({{ $item->id }})" class="meeting-row-remove">Remove</button>
                        </div>
                    @endforeach
                    @if($showAddAgendaItem)
                        <div class="meeting-agenda-form">
                            <input type="text" wire:model="newAgendaTitle" placeholder="Agenda item">
                            <input type="number" wire:model="newAgendaDuration" placeholder="Minutes">
                            <button type="button" wire:click="addAgendaItem" class="desk-button-primary">Add</button>
                            <button type="button" wire:click="$set('showAddAgendaItem', false)" class="desk-link">Cancel</button>
                        </div>
                    @else
                        <button type="button" wire:click="$set('showAddAgendaItem', true)" class="desk-link">+ Add agenda item</button>
                    @endif
                    <label class="meeting-field"><span>Agenda notes</span><textarea wire:model="agendaNotes" rows="5"></textarea></label>
                    <button type="button" wire:click="saveAgendaNotes" class="desk-button-secondary">Save agenda notes</button>
                </div>
            </details>

            <details class="meeting-detail-disclosure">
                <summary><span><small>Shared context</small>Team thread</span><span>{{ $threadEntries->count() }}</span></summary>
                <div>
                    <form wire:submit="postThreadMessage" class="meeting-thread-form">
                        <textarea wire:model="threadMessage" rows="3" placeholder="Post a quick update for the team…"></textarea>
                        <button type="submit" class="desk-button-primary">Post update</button>
                        <button type="button" wire:click="postSorryToMissIt" class="desk-link">Post “sorry to miss it”</button>
                    </form>
                    @forelse($threadEntries as $entry)
                        <article class="meeting-thread-entry"><div><strong>{{ $entry->author?->name ?? $entry->author_label ?? 'Staff' }}</strong><span>{{ optional($entry->captured_at ?? $entry->created_at)->diffForHumans() }}</span></div><p>{{ $entry->content }}</p></article>
                    @empty<p class="meeting-empty-copy">No team updates yet.</p>@endforelse
                </div>
            </details>

            @if($isRecurring)
                <details class="meeting-detail-disclosure">
                    <summary><span><small>{{ $seriesMeetings->count() }} meetings in this series</small>Recurring journal</span><span>+</span></summary>
                    <div class="meeting-thread-form"><textarea wire:model="seriesJournalEntry" rows="4" placeholder="Add an ongoing note for this series…"></textarea><button type="button" wire:click="addSeriesJournalEntry" class="desk-button-primary">Add entry</button><button type="button" wire:click="downloadSeriesMarkdown" class="desk-button-secondary">Download .md</button></div>
                </details>
            @endif
        </main>

        <aside class="meeting-detail-rail">
            <section class="meeting-rail-section">
                <p class="desk-kicker">How WRK read this</p>
                <strong>{{ $purposeLabel }}</strong>
                @if($aiFocus)<p>{{ $aiFocus }}</p>@else<p>No custom focus was provided.</p>@endif
            </section>

            <section class="meeting-rail-section">
                <div class="meeting-suggestion-header"><strong>People</strong>@if(!$editing)<button type="button" wire:click="$set('showAddPersonForm', true)" class="desk-link">Add</button>@endif</div>
                @forelse($meeting->people as $person)
                    <a href="{{ route('people.show', $person) }}" wire:navigate class="meeting-rail-link"><strong>{{ $person->name }}</strong><span>{{ $person->title ?: $person->organization?->name }}</span></a>
                @empty<p class="meeting-empty-copy">No people linked.</p>@endforelse
                @if($showAddPersonForm)
                    <div class="meeting-rail-form"><input wire:model="newPersonName" placeholder="Name"><input wire:model="newPersonEmail" placeholder="Email"><input wire:model="newPersonTitle" placeholder="Title"><button wire:click="addNewPerson" class="desk-button-primary">Add person</button><button wire:click="$set('showAddPersonForm', false)" class="desk-link">Cancel</button></div>
                @endif
            </section>

            <section class="meeting-rail-section">
                <div class="meeting-suggestion-header"><strong>Organizations</strong>@if(!$editing)<button type="button" wire:click="$set('showAddOrganizationForm', true)" class="desk-link">Add</button>@endif</div>
                @forelse($meeting->organizations as $org)<a href="{{ route('organizations.show', $org) }}" wire:navigate class="meeting-rail-link"><strong>{{ $org->name }}</strong><span>{{ Str::headline($org->type ?? 'organization') }}</span></a>@empty<p class="meeting-empty-copy">No organizations linked.</p>@endforelse
                @if($showAddOrganizationForm)
                    <div class="meeting-rail-form"><input wire:model="newOrganizationName" placeholder="Name"><select wire:model="newOrganizationType"><option value="other">Other</option><option value="nonprofit">Nonprofit</option><option value="government">Government</option><option value="company">Company</option><option value="association">Association</option><option value="foundation">Foundation</option></select><button wire:click="addNewOrganization" class="desk-button-primary">Add organization</button><button wire:click="$set('showAddOrganizationForm', false)" class="desk-link">Cancel</button></div>
                @endif
            </section>

            <section class="meeting-rail-section">
                <strong>Topics</strong>
                <div class="meeting-chip-list">@forelse($meeting->issues as $issue)<span>{{ $issue->name }}</span>@empty<span>No topics linked</span>@endforelse</div>
            </section>

            <section class="meeting-rail-section">
                <strong>Files</strong>
                @forelse($meeting->attachments as $attachment)<a href="{{ route('files.download', ['type' => 'meeting-attachment', 'id' => $attachment->id]) }}" target="_blank" class="meeting-rail-link"><strong>{{ $attachment->original_filename }}</strong><span>{{ Str::headline($attachment->file_type) }}</span></a>@empty<p class="meeting-empty-copy">No attachments.</p>@endforelse
            </section>

            @if($relatedMeetings->count())
                <section class="meeting-rail-section">
                    <strong>Related history</strong>
                    @foreach($relatedMeetings as $related)<a href="{{ route('meetings.show', $related) }}" wire:navigate class="meeting-rail-link"><strong>{{ $related->title }}</strong><span>{{ $related->meeting_date->format('M j, Y') }}</span></a>@endforeach
                </section>
            @endif
        </aside>
    </div>

    @if($showPrepModal)
        <div class="desk-modal-backdrop" role="dialog" aria-modal="true">
            <div class="desk-modal-panel meeting-prep-modal">
                <header><div><p class="desk-kicker">Before the conversation</p><h2>✦ Build a prep brief</h2></div><button type="button" wire:click="closePrepModal">×</button></header>
                <div class="meeting-prep-body">
                    @if(!$prepAnalysis)
                        <label class="meeting-field"><span>Additional context <em>Optional</em></span><textarea wire:model="prepInputText" rows="7" placeholder="Paste an agenda, email thread, or background material…"></textarea><small>WRK combines this with linked people, organizations, past meetings, and projects.</small></label>
                    @elseif(isset($prepAnalysis['error']))
                        <div class="desk-alert desk-alert-danger">{{ $prepAnalysis['error'] }}</div>
                    @elseif(isset($prepAnalysis['raw']))
                        <div class="meeting-prose">{!! Str::markdown($prepAnalysis['raw'], ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                    @else
                        <div class="meeting-prep-results">
                            @if(data_get($prepAnalysis, 'attendee_analysis.key_people'))<section><h3>People and context</h3><ul>@foreach(data_get($prepAnalysis, 'attendee_analysis.key_people', []) as $person)<li>{{ $person }}</li>@endforeach</ul><p>{{ data_get($prepAnalysis, 'attendee_analysis.organization_context') }}</p></section>@endif
                            @foreach(['suggested_topics' => 'Suggested topics', 'key_questions' => 'Questions to ask', 'potential_asks' => 'Potential asks'] as $key => $label)
                                @if(!empty($prepAnalysis[$key]))<section><h3>{{ $label }}</h3><ul>@foreach($prepAnalysis[$key] as $item)<li>{{ $item }}</li>@endforeach</ul></section>@endif
                            @endforeach
                            @if(!empty($prepAnalysis['relevant_history']))<section><h3>Relevant history</h3><p>{{ $prepAnalysis['relevant_history'] }}</p></section>@endif
                            @if(!empty($prepAnalysis['preparation_notes']))<section><h3>Preparation notes</h3><p>{{ $prepAnalysis['preparation_notes'] }}</p></section>@endif
                        </div>
                    @endif
                </div>
                <footer>
                    <button type="button" wire:click="closePrepModal" class="desk-link">Close</button>
                    @if(!$prepAnalysis)<button type="button" wire:click="analyzePrepMaterial" wire:loading.attr="disabled" class="desk-button-dark"><span wire:loading.remove wire:target="analyzePrepMaterial">✦ Generate brief</span><span wire:loading wire:target="analyzePrepMaterial">Analyzing…</span></button>
                    @else<button type="button" wire:click="$set('prepAnalysis', null)" class="desk-button-secondary">Start over</button>@if(!isset($prepAnalysis['error']))<button type="button" wire:click="applyPrepToMeeting" class="desk-button-primary">Add to prep notes</button>@endif @endif
                </footer>
            </div>
        </div>
    @endif
</div>
