<template>
  <div v-if="event">
    <div class="page-header">
      <h1>{{ event.name }}</h1>
      <router-link to="/events" class="btn btn-outline">Zurück</router-link>
    </div>

    <p v-if="event.description" style="margin-bottom:1rem;color:var(--text-light)">{{ event.description }}</p>
    <p v-if="event.location" style="margin-bottom:1.5rem">📍 {{ event.location }}</p>

    <div class="tabs">
      <button :class="['tab', { active: tab === 'dates' }]" @click="tab = 'dates'">Termine</button>
      <button :class="['tab', { active: tab === 'participants' }]" @click="tab = 'participants'">Teilnehmer</button>
      <button :class="['tab', { active: tab === 'scores' }]" @click="tab = 'scores'">Ergebnisse</button>
    </div>

    <!-- DATES TAB -->
    <div v-if="tab === 'dates'">
      <div class="card">
        <div class="card-header">
          <h3>Termine</h3>
          <button class="btn btn-sm btn-primary" @click="showDateModal = true">+ Termin</button>
        </div>
        <div v-if="event.dates && event.dates.length">
          <table>
            <thead>
              <tr><th>Datum</th><th>Von</th><th>Bis</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="d in event.dates" :key="d.id">
                <td>{{ formatDate(d.event_date) }}</td>
                <td>{{ d.start_time }}</td>
                <td>{{ d.end_time }}</td>
                <td><button class="btn btn-sm btn-danger" @click="onDeleteDate(d.id)">Löschen</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state"><p>Keine Termine</p></div>
      </div>
    </div>

    <!-- PARTICIPANTS TAB -->
    <div v-if="tab === 'participants'">
      <div class="card">
        <div class="card-header">
          <h3>Teilnehmer ({{ event.participants?.length || 0 }})</h3>
          <button class="btn btn-sm btn-primary" @click="showParticipantModal = true">+ Teilnehmer</button>
        </div>
        <div v-if="event.participants && event.participants.length">
          <table>
            <thead>
              <tr><th>Name</th><th>Jahrgang</th><th>Geschlecht</th><th>Verein</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="p in event.participants" :key="p.id">
                <td>{{ p.last_name }}, {{ p.first_name }}</td>
                <td>{{ p.birth_year }}</td>
                <td>{{ genderLabel(p.gender) }}</td>
                <td>{{ p.club_name || '-' }}</td>
                <td><button class="btn btn-sm btn-danger" @click="onRemoveParticipant(p.id)">Entfernen</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state"><p>Keine Teilnehmer</p></div>
      </div>
    </div>

    <!-- SCORES TAB -->
    <div v-if="tab === 'scores'">
      <div class="card">
        <div class="card-header">
          <h3>Ergebnisse erfassen</h3>
          <button class="btn btn-sm btn-primary" @click="showScoreModal = true">+ Ergebnis</button>
        </div>
        <div v-if="scores.length">
          <table>
            <thead>
              <tr><th>Schütze</th><th>Punkte</th><th>Termin</th><th>Notizen</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="s in scores" :key="s.id">
                <td>{{ s.last_name }}, {{ s.first_name }}</td>
                <td><strong>{{ s.points }}</strong></td>
                <td>{{ s.event_date ? formatDate(s.event_date) : 'Gesamt' }}</td>
                <td>{{ s.notes || '-' }}</td>
                <td><button class="btn btn-sm btn-danger" @click="onDeleteScore(s.id)">Löschen</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state"><p>Keine Ergebnisse</p></div>
      </div>
    </div>

    <!-- Add Date Modal -->
    <div v-if="showDateModal" class="modal-overlay" @click.self="showDateModal = false">
      <div class="modal">
        <h3>Termin hinzufügen</h3>
        <div class="form-group">
          <label>Datum</label>
          <input v-model="dateForm.event_date" type="date" class="form-control" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Von</label>
            <input v-model="dateForm.start_time" type="time" class="form-control" />
          </div>
          <div class="form-group">
            <label>Bis</label>
            <input v-model="dateForm.end_time" type="time" class="form-control" />
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showDateModal = false">Abbrechen</button>
          <button class="btn btn-primary" @click="onAddDate">Hinzufügen</button>
        </div>
      </div>
    </div>

    <!-- Add Participant Modal -->
    <div v-if="showParticipantModal" class="modal-overlay" @click.self="showParticipantModal = false">
      <div class="modal">
        <h3>Teilnehmer hinzufügen</h3>
        <div class="form-group">
          <label>Schütze suchen</label>
          <input v-model="searchQuery" class="form-control" placeholder="Name eingeben..." @input="searchShooters" />
        </div>
        <div v-if="availableShooters.length" style="max-height:300px;overflow-y:auto">
          <table>
            <tbody>
              <tr v-for="s in availableShooters" :key="s.id" style="cursor:pointer" @click="onAddParticipant(s.id)">
                <td>{{ s.last_name }}, {{ s.first_name }}</td>
                <td>{{ s.birth_year }}</td>
                <td>{{ s.club_name || '-' }}</td>
                <td><button class="btn btn-sm btn-success">Hinzufügen</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else-if="searchQuery" style="color:var(--text-light);padding:1rem">Keine Schützen gefunden</p>
        <div v-if="participantError" class="alert alert-danger" style="margin-top:1rem">{{ participantError }}</div>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showParticipantModal = false">Schließen</button>
        </div>
      </div>
    </div>

    <!-- Add Score Modal -->
    <div v-if="showScoreModal" class="modal-overlay" @click.self="showScoreModal = false">
      <div class="modal">
        <h3>Ergebnis erfassen</h3>
        <div class="form-group">
          <label>Schütze *</label>
          <select v-model="scoreForm.shooter_id" class="form-control">
            <option value="">-- Schütze wählen --</option>
            <option v-for="p in event.participants" :key="p.id" :value="p.id">
              {{ p.last_name }}, {{ p.first_name }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Termin</label>
          <select v-model="scoreForm.event_date_id" class="form-control">
            <option value="">-- Gesamt --</option>
            <option v-for="d in event.dates" :key="d.id" :value="d.id">
              {{ formatDate(d.event_date) }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Punkte *</label>
          <input v-model.number="scoreForm.points" type="number" step="0.1" class="form-control" placeholder="Punkte" />
        </div>
        <div class="form-group">
          <label>Notizen</label>
          <input v-model="scoreForm.notes" class="form-control" placeholder="Optionale Notizen" />
        </div>
        <div v-if="scoreError" class="alert alert-danger">{{ scoreError }}</div>
        <div class="modal-actions">
          <button class="btn btn-outline" @click="showScoreModal = false">Abbrechen</button>
          <button class="btn btn-primary" @click="onAddScore">Speichern</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getEvent, addEventDate, deleteEventDate, addParticipant, removeParticipant, getShooters, getScores, createScore, deleteScore } from '../services/api'

const route = useRoute()
const event = ref(null)
const scores = ref([])
const tab = ref('dates')

const showDateModal = ref(false)
const showParticipantModal = ref(false)
const showScoreModal = ref(false)

const dateForm = ref({ event_date: '', start_time: '09:00', end_time: '17:00' })
const searchQuery = ref('')
const availableShooters = ref([])
const participantError = ref('')
const scoreForm = ref({ shooter_id: '', event_date_id: '', points: 0, notes: '' })
const scoreError = ref('')

const genderLabel = (g) => ({ m: 'Männlich', w: 'Weiblich', d: 'Divers' }[g] || g)

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const loadEvent = async () => {
  const res = await getEvent(route.params.id)
  event.value = res.data
}

const loadScores = async () => {
  const res = await getScores({ event_id: route.params.id })
  scores.value = res.data
}

const onAddDate = async () => {
  await addEventDate(route.params.id, dateForm.value)
  showDateModal.value = false
  dateForm.value = { event_date: '', start_time: '09:00', end_time: '17:00' }
  await loadEvent()
}

const onDeleteDate = async (dateId) => {
  if (confirm('Termin löschen?')) {
    await deleteEventDate(route.params.id, dateId)
    await loadEvent()
  }
}

const searchShooters = async () => {
  if (searchQuery.value.length < 1) {
    availableShooters.value = []
    return
  }
  const res = await getShooters({ search: searchQuery.value })
  const participantIds = (event.value.participants || []).map(p => p.id)
  availableShooters.value = res.data.filter(s => !participantIds.includes(s.id))
}

const onAddParticipant = async (shooterId) => {
  participantError.value = ''
  try {
    await addParticipant(route.params.id, shooterId)
    await loadEvent()
    await searchShooters()
  } catch (e) {
    participantError.value = e.response?.data?.error || 'Fehler'
  }
}

const onRemoveParticipant = async (shooterId) => {
  if (confirm('Teilnehmer entfernen?')) {
    await removeParticipant(route.params.id, shooterId)
    await loadEvent()
  }
}

const onAddScore = async () => {
  scoreError.value = ''
  if (!scoreForm.value.shooter_id) {
    scoreError.value = 'Bitte Schütze wählen'
    return
  }
  try {
    await createScore({
      event_id: parseInt(route.params.id),
      shooter_id: scoreForm.value.shooter_id,
      event_date_id: scoreForm.value.event_date_id || null,
      points: scoreForm.value.points,
      notes: scoreForm.value.notes,
    })
    showScoreModal.value = false
    scoreForm.value = { shooter_id: '', event_date_id: '', points: 0, notes: '' }
    await loadScores()
  } catch (e) {
    scoreError.value = e.response?.data?.error || 'Fehler'
  }
}

const onDeleteScore = async (id) => {
  if (confirm('Ergebnis löschen?')) {
    await deleteScore(id)
    await loadScores()
  }
}

onMounted(() => {
  loadEvent()
  loadScores()
})
</script>
