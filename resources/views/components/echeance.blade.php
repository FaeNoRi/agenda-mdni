{{-- Pastille d'échéance d'un projet ou d'une tâche (voir HasRetard::echeance). --}}
@props(['item', 'jours' => false])
@php $e = $item->echeance($jours); @endphp
<span class="pt-echeance pt-echeance--{{ $e['ton'] }}"><x-icone :name="$e['icone']" :size="13" /> {{ $e['texte'] }}</span>
