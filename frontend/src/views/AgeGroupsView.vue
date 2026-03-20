<template>
  <div>
    <div class="page-header">
      <h1>Altersklassen-Konfiguration</h1>
      <button class="btn btn-primary" @click="openCreate">+ Neue Altersklasse</button>
    </div>

    <div class="card">
      <div v-if="ageGroups.length" class="table-container">
        <table>
          <thead>
            <tr><th>Reihenf.</th><th>Name</th><th>Von Jahrgang</th><th>Bis Jahrgang</th><th>Geschlecht</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="ag in ageGroups" :key="ag.id">
              <td>{{ ag.sort_order }}</td>
              <td><strong>{{ ag.name }}</strong></td>
              <td>{{ ag.min_birth_year }}</td>
              <td>{{ ag.max_birth_year }}</td>
              <td>{{ genderFilterLabel(ag.gender) }}</td>
              <td style="white-space:nowrap">
                <button class="btn btn-sm btn-outline" @click="openEdit(ag)">Bearbeiten</button>
                <button class="btn btn-sm btn-danger" style="margin-left:0.25rem" @click="onDelete(ag.id)">Löschen</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <p>Keine Altersklassen konfiguriert.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Hinweise</h3></div>
      <ul style="padding-left:1.5rem;font-size:0.9rem;color:var(--text-light)">
        <li>Jahrgänge werden inklusiv gewertet (von - bis)</li>
        <li>Geschlecht "Alle" bedeutet, dass m/w/d zusammen gewertet werden</li>
        <li>Die Reihenfolge bestimmt die Sortierung in der Auswertung</li>
      </ul>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <h3>{{ editId ? 'Altersklasse bearbeiten' : 'Neue Altersklasse' }}</h3>
        <div class="form-group">
          <label>Name *</label>
          <input v-model="form.name" class="form-control" placeholder="z.B. Junioren" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Von Jahrgang *</label>
            <input v-model.number="form.min_birth_year" type="number" class="form-control" placeholder="z.B. 2004" />
          </div>
          <div class="form-group">
            <label>Bis Jahrgang *</label>
            <input v-model.number="form.max_birth_year" type="number" class="form-control" placeholder="z.B. 2007" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Geschlecht</label>
            <select v-model="form.gender" class="form-control">
              <option value="all">Alle</option>
              <option value="m">Männlich</option>
              <option value="w">Weiblich</option>
            </select>
          </div>
          <div class="form-group">
            <label>Reihenfolge</label>
            <input v-model.number="form.sort_order" type="number" class="form-control" />
          </div>
        </div>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showModal = false">Abbrechen</button>
          <button class="btn btn-primary" @click="onSave">Speichern</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getAgeGroups, createAgeGroup, updateAgeGroup, deleteAgeGroup } from '../services/api'

const ageGroups = ref([])
const showModal = ref(false)
const editId = ref(null)
const error = ref('')

const emptyForm = () => ({
  name: '', min_birth_year: '', max_birth_year: '', gender: 'all', sort_order: 0
})
const form = ref(emptyForm())

const genderFilterLabel = (g) => ({ m: 'Männlich', w: 'Weiblich', all: 'Alle' }[g] || g)

const loadAgeGroups = async () => {
  const res = await getAgeGroups()
  ageGroups.value = res.data
}

const openCreate = () => {
  editId.value = null
  form.value = emptyForm()
  error.value = ''
  showModal.value = true
}

const openEdit = (ag) => {
  editId.value = ag.id
  form.value = { ...ag }
  error.value = ''
  showModal.value = true
}

const onSave = async () => {
  error.value = ''
  if (!form.value.name || !form.value.min_birth_year || !form.value.max_birth_year) {
    error.value = 'Name und Jahrgänge sind Pflichtfelder'
    return
  }
  try {
    if (editId.value) {
      await updateAgeGroup(editId.value, form.value)
    } else {
      await createAgeGroup(form.value)
    }
    showModal.value = false
    await loadAgeGroups()
  } catch (e) {
    error.value = e.response?.data?.error || 'Fehler'
  }
}

const onDelete = async (id) => {
  if (confirm('Altersklasse löschen?')) {
    await deleteAgeGroup(id)
    await loadAgeGroups()
  }
}

onMounted(loadAgeGroups)
</script>
