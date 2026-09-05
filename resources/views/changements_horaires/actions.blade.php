<a href="{{ route('changements_horaires.edit',$item) }}" class="btn btn-sm btn-icon">
    <i class="ti ti-edit"></i>
</a>
<form action="{{ route('changements_horaires.destroy',$item) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-icon text-danger">
        <i class="ti ti-trash"></i>
    </button>
</form>
