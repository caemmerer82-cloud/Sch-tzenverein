<template>
  <div>
    <div class="page-header">
      <h1>Schützen</h1>
      <button class="btn btn-primary" @click="openCreate">+ Neuer Schütze</button>
    </div>

    <div class="card" style="margin-bottom:1rem">
      <input v-model="search" class="form-control" placeholder="Schützen suchen..." @input="loadShooters" />
    </div>

    <div v-if="shooters.length === 0" class="empty-state card">
      <p>Keine Schützen gefunden.</p>
    </div>

    <div v-else class="card">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Jahrgang</th>
              <th>Geschlecht</th>
              <th>Verein</th>
              <th>E-Mail</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in shooters" :key="s.id">
              <td><strong>{{ s.last_name }}, {{ s.first_name }}</strong></td>
              <td>{{ s.birth_year }}</td>
              <td>{{ genderLabel(s.gender) }}</td>
              <td>{{ s.club_name || '-' }}</td>
              <td>{{ s.email || '-' }}</td>
              <td style="white-space:nowrap">
                <button class="btn btn-sm btn-outline" @click="openEdit(s)">Bearbeiten</button>
                <button class="btn btn-sm btn-danger" style="margin-left:0.25rem" @click="onDelete(s.id)">Löschen</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <h3>{{ editId ? 'Schütze bearbeiten' : 'Neuer Schütze' }}</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Vorname *</label>
            <input v-model="form.first_name" class="form-control" />
          </div>
          <div class="form-group">
            <label>Nachname *</label>
            <input v-model="form.last_name" class="form-control" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Geburtsjahr *</label>
            <input v-model.number="form.birth_year" type="number" class="form-control" placeholder="z.B. 1990" />
          </div>
          <div class="form-group">
            <label>Geschlecht</label>
            <select v-model="form.gender" class="form-control">
              <option value="m">Männlich</option>
              <option value="w">Weiblich</option>
              <option value="d">Divers</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Verein</label>
          <input v-model="form.club_name" class="form-control" placeholder="Vereinsname (optional)" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>E-Mail</label>
            <input v-model="form.email" type="email" class="form-control" />
          </div>
          <div class="form-group">
            <label>Telefon</label>
            <input v-model="form.phone" class="form-control" />
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
import { getShooters, createShooter, updateShooter, deleteShooter } from '../services/api'

const shooters = ref([])
const showModal = ref(false)
const editId = ref(null)
const search = ref('')
const error = ref('')

const emptyForm = () => ({
  first_name: '', last_name: '', birth_year: '', gender: 'm', club_name: '', email: '', phone: ''
})
const form = ref(emptyForm())

const genderLabel = (g) => ({ m: 'Männlich', w: 'Weiblich', d: 'Divers' }[g] || g)

const loadShooters = async () => {
  const res = await getShooters({ search: search.value || undefined })
  shooters.value = res.data
}

const openCreate = () => {
  editId.value = null
  form.value = emptyForm()
  error.value = ''
  showModal.value = true
}

const openEdit = (s) => {
  editId.value = s.id
  form.value = { ...s }
  error.value = ''
  showModal.value = true
}

const onSave = async () => {
  error.value = ''
  if (!form.value.first_name || !form.value.last_name || !form.value.birth_year) {
    error.value = 'Vorname, Nachname und Geburtsjahr sind Pflichtfelder'
    return
  }
  try {
    if (editId.value) {
      await updateShooter(editId.value, form.value)
    } else {
      await createShooter(form.value)
    }
    showModal.value = false
    await loadShooters()
  } catch (e) {
    error.value = e.response?.data?.error || 'Fehler beim Speichern'
  }
}

const onDelete = async (id) => {
  if (confirm('Schütze wirklich löschen?')) {
    await deleteShooter(id)
    await loadShooters()
  }
}

onMounted(loadShooters)
</script>
