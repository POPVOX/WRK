<section class="meeting-rail-section">
    <div class="meeting-suggestion-header"><strong>{{ $heading }}</strong></div>
    <div class="meeting-inline-add">
        <input type="text" wire:model="{{ $inputModel }}" wire:keydown.enter.prevent="{{ $addMethod }}" placeholder="Add {{ strtolower($heading) }}…">
        <button type="button" wire:click="{{ $addMethod }}">Add</button>
    </div>
    <div class="meeting-chip-list">
        @foreach($selectedModels as $model)
            <span>{{ $model->name }} <button type="button" wire:click="{{ $removeMethod }}({{ $model->id }})">×</button></span>
        @endforeach
    </div>

    @if(count($suggestions))
        <div class="meeting-relationship-proposals">
            <div class="meeting-suggestion-header">
                <span>✦ AI suggestions — accept or reject</span>
                <button type="button" wire:click="{{ $acceptAllMethod }}" class="desk-link">Accept all</button>
            </div>
            @foreach($suggestions as $suggestion)
                <div wire:key="suggested-{{ $wirePrefix }}-{{ sha1($suggestion) }}">
                    <span>{{ $suggestion }}</span>
                    <span>
                        <button type="button" wire:click="{{ $acceptMethod }}('{{ sha1($suggestion) }}')" aria-label="Accept {{ $suggestion }}">✓</button>
                        <button type="button" wire:click="{{ $rejectMethod }}('{{ sha1($suggestion) }}')" aria-label="Reject {{ $suggestion }}">×</button>
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</section>
