{{--
    Préférences de notifications : les types « imposés » sont toujours reçus ; les autres se désactivent ici.
    Variables : $user.
--}}
@php
    $refus = $user->notificationPreferences()->where('actif', false)->pluck('type')->all();
@endphp
<section id="notifications">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Notifications</h2>
        <p class="mt-1 text-sm text-gray-600">
            Elles s'affichent dans le tiroir de la cloche, en haut à droite. Les notifications marquées « Toujours actives » ne peuvent pas être désactivées.
        </p>
    </header>

    @if(session('status') === 'notifications-updated')
        <div class="alert alert-success mt-4 mb-0" role="status">Vos préférences sont enregistrées.</div>
    @endif

    <form method="post" action="{{ route('profile.notifications') }}" class="mt-6">
        @csrf
        @method('patch')

        @foreach(\App\Support\NotificationTypes::CATEGORIES as $cle => [$nom, $couleur])
            <div class="mb-4">
                <div class="mb-2 d-flex align-items-center gap-2" style="font-weight: 700;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: {{ $couleur }};"></span>{{ $nom }}
                </div>
                @foreach(\App\Support\NotificationTypes::parCategorie($cle) as $type => [$libelle, $imposee])
                    <label class="d-flex align-items-start gap-2 mb-2" style="{{ $imposee ? 'cursor: default;' : 'cursor: pointer;' }}">
                        @if($imposee)
                            <input type="checkbox" class="form-check-input mt-1" checked disabled aria-label="{{ $libelle }} (toujours active)">
                            <span>{{ $libelle }} <span class="badge bg-secondary-lt ms-1">Toujours active</span></span>
                        @else
                            <input type="checkbox" class="form-check-input mt-1" name="types[]" value="{{ $type }}" @checked(!in_array($type, $refus, true))>
                            <span>{{ $libelle }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
        @endforeach

        <button type="submit" class="btn pt-btn-doux" style="padding: .5rem 1.1rem; border-radius: 8px; font-weight: 600; background: color-mix(in srgb, var(--tblr-primary) 13%, white); color: var(--tblr-primary); border: 1px solid transparent;">Enregistrer</button>
    </form>
</section>
