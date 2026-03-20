<template>
  <div>
    <div class="page-header">
      <h1>Events</h1>
      <button class="btn btn-primary" @click="showCreateModal = true">+ Neues Event</button>
    </div>

    <div v-if="events.length === 0" class="empty-state card">
      <p>Noch keine Events vorhanden.</p>
      <button class="btn btn-primary" @click="showCreateModal = true">Erstes Event anlegen</button>
    </div>

    <div v-for="event in events" :key="event.id" class="card">
      <div class="card-header">
        <h2>{{ event.name }}</h2>
        <div style="display:flex;gap:0.5rem">
          <router-link :to="`/events/${event.id}`" class="btn btn-sm btn-outline">Details</router-link>
          <button class="btn btn-sm btn-danger" @click="onDelete(event.id)">Löschen</button>
        </div>
      </div>
      <p v-if="event.description" style="margin-bottom:0.5rem;color:var(--text-light)">{{ event.description }}</p>
      <div style="display:flex;gap:1.5rem;font-size:0.9rem">
        <span v-if="event.location">📍 {{ event.location }}</span>
        <span class="badge badge-primary">{{ event.participant_count }} Teilnehmer</span>
        <span class="badge badge-info">{{ event.date_count }} Termine</span>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h3>Neues Event anlegen</h3>
        <div class="form-group">
          <label>Name *</label>
          <input v-model="form.name" class="form-control" placeholder="Event-Name" />
        </div>
        <div class="form-group">
          <label>Beschreibung</label>
          <textarea v-model="form.description" class="form-control" placeholder="Beschreibung"></textarea>
        </div>
        <div class="form-group">
          <label>Ort</label>
          <input v-model="form.location" class="form-control" placeholder="Veranstaltungsort" />
        </div>

        <h4 style="margin:1rem 0 0.5rem;color:var(--primary)">Termine</h4>
        <div v-for="(d, i) in form.dates" :key="i" class="form-row-3" style="margin-bottom:0.5rem">
          <div class="form-group">
            <label>Datum</label>
            <input v-model="d.event_date" type="date" class="form-control" />
          </div>
          <div class="form-group">
            <label>Von</label>
            <input v-model="d.start_time" type="time" class="form-control" />
          </div>
          <div class="form-group">
            <label>Bis</label>
            <input v-model="d.end_time" type="time" class="form-control" />
          </div>
        </div>
        <button class="btn btn-sm btn-secondary" @click="addDate" style="margin-bottom:1rem">+ Termin hinzufügen</button>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="modal-actions">
          <button class="btn btn-outline" @click="showCreateModal = false">Abbrechen</button>
          <button class="btn btn-primary" @click="onCreate">Erstellen</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getEvents, createEvent, deleteEvent } from '../services/api'

const events = ref([])
const showCreateModal = ref(false)
const error = ref('')
const form = ref({
  name: '',
  description: '',
  location: '',
  dates: [{ event_date: '', start_time: '09:00', end_time: '17:00' }],
})

const loadEvents = async () => {
  const res = await getEvents()
  events.value = res.data
}

const addDate = () => {
  form.value.dates.push({ event_date: '', start_time: '09:00', end_time: '17:00' })
}

const onCreate = async () => {
  error.value = ''
  if (!form.value.name) {
    error.value = 'Name ist erforderlich'
    return
  }
  try {
    const dates = form.value.dates.filter(d => d.event_date)
    await createEvent({ ...form.value, dates })
    showCreateModal.value = false
    form.value = { name: '', description: '', location: '', dates: [{ event_date: '', start_time: '09:00', end_time: '17:00' }] }
    await loadEvents()
  } catch (e) {
    error.value = e.response?.data?.error || 'Fehler beim Erstellen'
  }
}

const onDelete = async (id) => {
  if (confirm('Event wirklich löschen?')) {
    await deleteEvent(id)
    await loadEvents()
  }
}

onMounted(loadEvents)
</script>
