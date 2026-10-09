{{-- Bascule Projets / Tâches. Variable : $actif ('projets' | 'taches'). --}}
<div class="pt-seg" role="tablist" aria-label="Vue">
    <a href="{{ route('projets.index') }}" class="pt-seg__btn {{ $actif === 'projets' ? 'on' : '' }}" role="tab" aria-selected="{{ $actif === 'projets' ? 'true' : 'false' }}">
        <x-icone name="folders" :size="15" /> Projets
    </a>
    <a href="{{ route('taches.index') }}" class="pt-seg__btn {{ $actif === 'taches' ? 'on' : '' }}" role="tab" aria-selected="{{ $actif === 'taches' ? 'true' : 'false' }}">
        <x-icone name="list-check" :size="15" /> Tâches
    </a>
</div>
