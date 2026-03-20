<template>
  <div>
    <div class="page-header">
      <h1>Anmeldung & Zeitslots</h1>
    </div>

    <!-- Step 1: Select Event -->
    <div class="card">
      <div class="card-header">
        <h3>1. Event auswählen</h3>
      </div>
      <select v-model="selectedEventId" class="form-control" @change="onEventChange">
        <option value="">-- Event wählen --</option>
        <option v-for="e in events" :key="e.id" :value="e.id">{{ e.name }}</option>
      </select>
    </div>

    <template v-if="selectedEventId && eventDates.length">
      <!-- Step 2: Select Date -->
      <div class="card">
        <div class="card-header">
          <h3>2. Termin auswählen</h3>
        </div>
        <select v-model="selectedDateId" class="form-control" @change="loadTimeslots">
          <option value="">-- Termin wählen --</option>
          <option v-for="d in eventDates" :key="d.id" :value="d.id">
            {{ formatDate(d.event_date) }} ({{ d.start_time }} - {{ d.end_time }})
          </option>
        </select>
      </div>

      <!-- Step 3: Select Timeslots -->
      <div v-if="selectedDateId && timeslots.length" class="card">
        <div class="card-header">
          <h3>3. Zeitslots auswählen (15 Min.)</h3>
          <span class="badge badge-info">{{ selectedSlots.length }} ausgewählt</span>
        </div>
        <div class="timeslot-grid">
          <div
            v-for="ts in timeslots"
            :key="ts.id"
            :class="['timeslot', {
              'timeslot-selected': selectedSlots.includes(ts.id),
              'timeslot-full': ts.reserved_count >= ts.max_participants
            }]"
            @click="toggleSlot(ts)"
          >
            <div class="timeslot-time">{{ ts.start_time.slice(0,5) }} - {{ ts.end_time.slice(0,5) }}</div>
            <div class="timeslot-info">{{ ts.reserved_count }}/{{ ts.max_participants }} belegt</div>
          </div>
        </div>
      </div>

      <!-- Step 4: Registration Form -->
      <div v-if="selectedSlots.length" class="card">
        <div class="card-header">
          <h3>4. Anmeldedaten</h3>
        </div>

        <div class="form-group">
          <label>Anmeldungsart</label>
          <select v-model="regForm.is_group" class="form-control">
            <option :value="false">Einzelschütze</option>
            <option :value="true">Vereinsgruppe</option>
          </select>
        </div>

        <template v-if="!regForm.is_group">
          <div class="form-group">
            <label>Schütze auswählen</label>
            <select v-model="regForm.shooter_id" class="form-control">
              <option value="">-- Schütze wählen --</option>
              <option v-for="s in shooters" :key="s.id" :value="s.id">
                {{ s.last_name }}, {{ s.first_name }} ({{ s.club_name || 'Kein Verein' }})
              </option>
            </select>
          </div>
        </template>

        <template v-else>
          <div class="form-group">
            <label>Gruppenname / Verein *</label>
            <input v-model="regForm.group_name" class="form-control" placeholder="z.B. SV Musterstadt" />
          </div>
          <div class="form-group">
            <label>Anzahl Teilnehmer</label>
            <input v-model.number="regForm.participant_count" type="number" min="1" class="form-control" />
          </div>
        </template>

        <div class="form-row-3">
          <div class="form-group">
            <label>Kontaktname</label>
            <input v-model="regForm.contact_name" class="form-control" />
          </div>
          <div class="form-group">
            <label>E-Mail</label>
            <input v-model="regForm.contact_email" type="email" class="form-control" />
          </div>
          <div class="form-group">
            <label>Telefon</label>
            <input v-model="regForm.contact_phone" class="form-control" />
          </div>
        </div>

        <div v-if="regError" class="alert alert-danger">{{ regError }}</div>
        <div v-if="regSuccess" class="alert alert-success">{{ regSuccess }}</div>

        <button class="btn btn-primary" @click="onRegister" style="margin-top:0.5rem">Anmelden</button>
      </div>
    </template>

    <div v-if="selectedEventId && eventDates.length === 0" class="card empty-state">
      <p>Dieses Event hat noch keine Termine.</p>
    </div>

    <!-- Existing Reservations -->
    <div v-if="selectedEventId && reservations.length" class="card" style="margin-top:1.5rem">
      <div class="card-header">
        <h3>Bestehende Reservierungen</h3>
      </div>
      <div class="table-container">
        <table>
          <thead>
            <tr><th>Datum</th><th>Zeit</th><th>Name</th><th>Typ</th><th>Anz.</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="r in reservations" :key="r.id">
              <td>{{ formatDate(r.event_date) }}</td>
              <td>{{ r.slot_start?.slice(0,5) }} - {{ r.slot_end?.slice(0,5) }}</td>
              <td>{{ r.is_group ? r.group_name : (r.last_name ? `${r.last_name}, ${r.first_name}` : r.contact_name) }}</td>
              <td><span :class="['badge', r.is_group ? 'badge-info' : 'badge-primary']">{{ r.is_group ? 'Gruppe' : 'Einzel' }}</span></td>
              <td>{{ r.participant_count }}</td>
              <td><button class="btn btn-sm btn-danger" @click="onDeleteReservation(r.id)">Stornieren</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getEvents, getEventDates, getShooters, getTimeslots, getReservations, createReservation, deleteReservation } from '../services/api'

const events = ref([])
const eventDates = ref([])
const timeslots = ref([])
const shooters = ref([])
const reservations = ref([])

const selectedEventId = ref('')
const selectedDateId = ref('')
const selectedSlots = ref([])

const regForm = ref({
  is_group: false,
  shooter_id: '',
  group_name: '',
  participant_count: 1,
  contact_name: '',
  contact_email: '',
  contact_phone: '',
})

const regError = ref('')
const regSuccess = ref('')

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const loadEvents = async () => {
  const res = await getEvents()
  events.value = res.data
}

const onEventChange = async () => {
  selectedDateId.value = ''
  selectedSlots.value = []
  timeslots.value = []
  if (!selectedEventId.value) {
    eventDates.value = []
    reservations.value = []
    return
  }
  const [datesRes, resRes] = await Promise.all([
    getEventDates(selectedEventId.value),
    getReservations({ event_id: selectedEventId.value }),
  ])
  eventDates.value = datesRes.data
  reservations.value = resRes.data
}

const loadTimeslots = async () => {
  selectedSlots.value = []
  if (!selectedDateId.value) {
    timeslots.value = []
    return
  }
  const res = await getTimeslots({ event_date_id: selectedDateId.value })
  timeslots.value = res.data
}

const toggleSlot = (ts) => {
  if (ts.reserved_count >= ts.max_participants) return
  const idx = selectedSlots.value.indexOf(ts.id)
  if (idx >= 0) {
    selectedSlots.value.splice(idx, 1)
  } else {
    selectedSlots.value.push(ts.id)
  }
}

const onRegister = async () => {
  regError.value = ''
  regSuccess.value = ''

  if (!regForm.value.is_group && !regForm.value.shooter_id) {
    regError.value = 'Bitte einen Schützen auswählen'
    return
  }
  if (regForm.value.is_group && !regForm.value.group_name) {
    regError.value = 'Bitte Gruppennamen angeben'
    return
  }

  try {
    await createReservation({
      time_slot_ids: selectedSlots.value,
      shooter_id: regForm.value.is_group ? null : regForm.value.shooter_id,
      group_name: regForm.value.is_group ? regForm.value.group_name : null,
      is_group: regForm.value.is_group,
      participant_count: regForm.value.is_group ? regForm.value.participant_count : 1,
      contact_name: regForm.value.contact_name,
      contact_email: regForm.value.contact_email,
      contact_phone: regForm.value.contact_phone,
    })
    regSuccess.value = 'Anmeldung erfolgreich!'
    selectedSlots.value = []
    await loadTimeslots()
    const resRes = await getReservations({ event_id: selectedEventId.value })
    reservations.value = resRes.data
  } catch (e) {
    regError.value = e.response?.data?.error || 'Fehler bei der Anmeldung'
  }
}

const onDeleteReservation = async (id) => {
  if (confirm('Reservierung stornieren?')) {
    await deleteReservation(id)
    const resRes = await getReservations({ event_id: selectedEventId.value })
    reservations.value = resRes.data
    if (selectedDateId.value) await loadTimeslots()
  }
}

onMounted(async () => {
  await loadEvents()
  const sRes = await getShooters()
  shooters.value = sRes.data
})
</script>
