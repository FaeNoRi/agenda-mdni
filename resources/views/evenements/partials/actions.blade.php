<a
    href="#"
    class="btn btn-sm btn-info"
    data-bs-toggle="modal"
    data-bs-target="#evenementModal"
    onclick="openEvenementForm({{ $evenement->id }}, event)"
>
    Modifier
</a>

<a
    href="#"
    class="btn btn-sm btn-success"
    data-bs-toggle="modal"
    data-bs-target="#evenementModal"
    onclick="openEvenementDuplicateForm({{ $evenement->id }}, event)"
>
    Copier
</a>

<button
    type="button"
    class="btn btn-sm btn-danger"
    onclick="confirmEvenementDelete({{ $evenement->id }}, @js($evenement->nom_event))"
>
    Supprimer
</button>
