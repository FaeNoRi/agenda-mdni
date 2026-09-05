<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title text-xl font-semibold">
            {{ __('Export de données') }}
        </h2>
    </x-slot>

    <div class="container-xl px-4 py-4" x-data="exportTool()">
        <form method="POST" action="{{ route('export') }}" class="card p-4 space-y-4">
            @csrf

            <!-- Choix du modèle -->
            <div>
                <label class="form-label">Modèle à exporter</label>
                <select class="form-select" x-model="selectedModel" name="model" required>
                    <option value="">-- Choisir un modèle --</option>
                    <template x-for="(label, model) in models" :key="model">
                        <option :value="model" x-text="label"></option>
                    </template>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Période - début</label>
                    <input type="date" name="filters[start_date]" class="form-control" />
                </div>
                <div>
                    <label class="form-label">Période - fin</label>
                    <input type="date" name="filters[end_date]" class="form-control" />
                </div>
            </div>
            
            <!-- Colonnes disponibles -->
            <div x-show="selectedModel">
                <label class="form-label mb-2">Colonnes à inclure</label>

                <div class="flex flex-wrap gap-3">
                    <template x-for="column in columnsForSelectedModel" :key="column">
                        <label class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                :value="column"
                                name="columns[]"
                                checked
                            >
                            <span class="form-check-label" x-text="column"></span>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Bouton d’export -->
            <div class="pt-2">
                <button type="submit" class="btn btn-primary">
                    Exporter vers Excel
                </button>
            </div>
        </form>
    </div>

    <script>
        function exportTool() {
            return {
                selectedModel: '',
                models: Object.fromEntries(
                    Object.entries({
                        User: 'Utilisateurs',
                        Salles: 'Salles',
                        Objets: 'Objets',
                        Evenements: 'Événements',
                        Conge: 'Congés',
                        ChangementHoraire: 'Changements d’horaires',
                        Materiels: 'Matériels'
                    }).sort((a, b) => a[1].localeCompare(b[1]))
                ),
                columns: [],

                async fetchColumns() {
                    if (!this.selectedModel) return;

                    const res = await fetch(`/export/columns/${this.selectedModel}`);
                    if (res.ok) {
                        this.columns = await res.json();
                    } else {
                        this.columns = [];
                    }
                },

                get columnsForSelectedModel() {
                    return this.columns;
                },

                // Automatiquement appelée à chaque changement de modèle
                init() {
                    this.$watch('selectedModel', () => this.fetchColumns());
                }
            }
        }
    </script>
</x-app-layout>
