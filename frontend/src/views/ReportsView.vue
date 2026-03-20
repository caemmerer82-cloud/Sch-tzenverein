<template>
  <div>
    <div class="page-header">
      <h1>Auswertung</h1>
    </div>

    <div class="tabs">
      <button :class="['tab', { active: tab === 'event' }]" @click="tab = 'event'">Event-Auswertung</button>
      <button :class="['tab', { active: tab === 'shooter' }]" @click="tab = 'shooter'">Schützen-Auswertung</button>
      <button :class="['tab', { active: tab === 'agegroup' }]" @click="tab = 'agegroup'">Altersklassen</button>
    </div>

    <!-- EVENT REPORT -->
    <div v-if="tab === 'event'">
      <div class="card">
        <div class="form-group">
          <label>Event auswählen</label>
          <select v-model="selectedEventId" class="form-control" @change="loadEventReport">
            <option value="">-- Event wählen --</option>
            <option v-for="e in events" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="eventReport">
        <div class="card">
          <div class="card-header">
            <h2>Gesamtwertung: {{ eventReport.event.name }}</h2>
          </div>
          <div v-if="eventReport.overall_ranking.length" class="table-container">
            <table>
              <thead>
                <tr><th>Platz</th><th>Name</th><th>Verein</th><th>Jahrgang</th><th>Gesamt</th><th>Durchschn.</th><th>Beste</th><th>Anzahl</th></tr>
              </thead>
              <tbody>
                <tr v-for="r in eventReport.overall_ranking" :key="r.shooter_id">
                  <td><span :class="'rank-' + r.rank">{{ r.rank }}.</span></td>
                  <td><strong>{{ r.last_name }}, {{ r.first_name }}</strong></td>
                  <td>{{ r.club_name || '-' }}</td>
                  <td>{{ r.birth_year }}</td>
                  <td><strong>{{ r.total_points }}</strong></td>
                  <td>{{ r.avg_points }}</td>
                  <td>{{ r.max_points }}</td>
                  <td>{{ r.score_count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-state"><p>Keine Ergebnisse vorhanden</p></div>
        </div>

        <!-- Per-Date Results -->
        <div v-for="dr in eventReport.date_results" :key="dr.date.id" class="card">
          <div class="card-header">
            <h3>{{ formatDate(dr.date.event_date) }} ({{ dr.date.start_time }} - {{ dr.date.end_time }})</h3>
          </div>
          <div v-if="dr.scores.length" class="table-container">
            <table>
              <thead>
                <tr><th>Platz</th><th>Name</th><th>Verein</th><th>Punkte</th><th>Notizen</th></tr>
              </thead>
              <tbody>
                <tr v-for="(s, i) in dr.scores" :key="i">
                  <td><span :class="'rank-' + (i+1)">{{ i + 1 }}.</span></td>
                  <td>{{ s.last_name }}, {{ s.first_name }}</td>
                  <td>{{ s.club_name || '-' }}</td>
                  <td><strong>{{ s.points }}</strong></td>
                  <td>{{ s.notes || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-state"><p>Keine Ergebnisse für diesen Termin</p></div>
        </div>
      </div>
    </div>

    <!-- SHOOTER REPORT -->
    <div v-if="tab === 'shooter'">
      <div class="card">
        <div class="form-group">
          <label>Schütze auswählen (oder leer lassen für Übersicht)</label>
          <select v-model="selectedShooterId" class="form-control" @change="loadShooterReport">
            <option value="">-- Alle Schützen (Übersicht) --</option>
            <option v-for="s in shooters" :key="s.id" :value="s.id">
              {{ s.last_name }}, {{ s.first_name }}
            </option>
          </select>
        </div>
      </div>

      <!-- All Shooters Summary -->
      <div v-if="!selectedShooterId && shootersSummary.length" class="card">
        <div class="card-header"><h2>Schützen-Übersicht</h2></div>
        <div class="table-container">
          <table>
            <thead>
              <tr><th>Name</th><th>Jahrgang</th><th>Geschlecht</th><th>Verein</th><th>Events</th><th>Gesamtpunkte</th><th>Durchschn.</th></tr>
            </thead>
            <tbody>
              <tr v-for="s in shootersSummary" :key="s.id">
                <td><strong>{{ s.last_name }}, {{ s.first_name }}</strong></td>
                <td>{{ s.birth_year }}</td>
                <td>{{ genderLabel(s.gender) }}</td>
                <td>{{ s.club_name || '-' }}</td>
                <td>{{ s.event_count }}</td>
                <td><strong>{{ parseFloat(s.total_points).toFixed(1) }}</strong></td>
                <td>{{ parseFloat(s.avg_points).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Single Shooter Detail -->
      <div v-if="selectedShooterId && shooterReport">
        <div class="card">
          <div class="card-header">
            <h2>{{ shooterReport.shooter.last_name }}, {{ shooterReport.shooter.first_name }}</h2>
          </div>
          <div class="grid-3" style="margin-bottom:1rem">
            <div class="stat-card card">
              <div class="stat-value">{{ shooterReport.shooter.birth_year }}</div>
              <div class="stat-label">Jahrgang</div>
            </div>
            <div class="stat-card card">
              <div class="stat-value">{{ genderLabel(shooterReport.shooter.gender) }}</div>
              <div class="stat-label">Geschlecht</div>
            </div>
            <div class="stat-card card">
              <div class="stat-value">{{ shooterReport.shooter.club_name || '-' }}</div>
              <div class="stat-label">Verein</div>
            </div>
          </div>
        </div>

        <div v-if="shooterReport.event_summary.length" class="card">
          <div class="card-header"><h3>Event-Zusammenfassung</h3></div>
          <table>
            <thead>
              <tr><th>Event</th><th>Gesamt</th><th>Durchschn.</th><th>Beste</th><th>Anzahl</th></tr>
            </thead>
            <tbody>
              <tr v-for="es in shooterReport.event_summary" :key="es.event_id">
                <td>{{ es.event_name }}</td>
                <td><strong>{{ es.total_points }}</strong></td>
                <td>{{ es.avg_points }}</td>
                <td>{{ es.max_points }}</td>
                <td>{{ es.score_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="shooterReport.scores.length" class="card">
          <div class="card-header"><h3>Einzelergebnisse</h3></div>
          <table>
            <thead>
              <tr><th>Event</th><th>Datum</th><th>Punkte</th></tr>
            </thead>
            <tbody>
              <tr v-for="sc in shooterReport.scores" :key="sc.id">
                <td>{{ sc.event_name }}</td>
                <td>{{ sc.event_date ? formatDate(sc.event_date) : '-' }}</td>
                <td><strong>{{ sc.points }}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- AGE GROUP REPORT -->
    <div v-if="tab === 'agegroup'">
      <div class="card">
        <div class="form-group">
          <label>Event auswählen (optional, leer = alle Events)</label>
          <select v-model="ageGroupEventId" class="form-control" @change="loadAgeGroupReport">
            <option value="">-- Alle Events --</option>
            <option v-for="e in events" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <button class="btn btn-primary" @click="loadAgeGroupReport">Auswertung laden</button>
      </div>

      <div v-for="ag in ageGroupReport" :key="ag.age_group.id" class="card">
        <div class="card-header">
          <h3>{{ ag.age_group.name }}</h3>
          <span class="badge badge-info">
            {{ ag.age_group.min_birth_year }} - {{ ag.age_group.max_birth_year }}
            <template v-if="ag.age_group.gender !== 'all'"> | {{ genderLabel(ag.age_group.gender) }}</template>
          </span>
        </div>
        <div v-if="ag.shooters.length" class="table-container">
          <table>
            <thead>
              <tr><th>Platz</th><th>Name</th><th>Jahrgang</th><th>Verein</th><th>Gesamt</th><th>Durchschn.</th></tr>
            </thead>
            <tbody>
              <tr v-for="s in ag.shooters" :key="s.shooter_id">
                <td><span :class="'rank-' + s.rank">{{ s.rank }}.</span></td>
                <td><strong>{{ s.last_name }}, {{ s.first_name }}</strong></td>
                <td>{{ s.birth_year }}</td>
                <td>{{ s.club_name || '-' }}</td>
                <td><strong>{{ s.total_points }}</strong></td>
                <td>{{ s.avg_points }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state" style="padding:1rem"><p>Keine Schützen in dieser Altersklasse</p></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getEvents, getShooters, getEventReport, getShooterReport, getShootersSummary, getAgeGroupReport } from '../services/api'

const events = ref([])
const shooters = ref([])
const tab = ref('event')

const selectedEventId = ref('')
const selectedShooterId = ref('')
const ageGroupEventId = ref('')

const eventReport = ref(null)
const shooterReport = ref(null)
const shootersSummary = ref([])
const ageGroupReport = ref([])

const genderLabel = (g) => ({ m: 'Männlich', w: 'Weiblich', d: 'Divers' }[g] || g)

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const loadEventReport = async () => {
  if (!selectedEventId.value) {
    eventReport.value = null
    return
  }
  const res = await getEventReport(selectedEventId.value)
  eventReport.value = res.data
}

const loadShooterReport = async () => {
  if (!selectedShooterId.value) {
    shooterReport.value = null
    const res = await getShootersSummary()
    shootersSummary.value = res.data
    return
  }
  const res = await getShooterReport(selectedShooterId.value)
  shooterReport.value = res.data
}

const loadAgeGroupReport = async () => {
  const res = await getAgeGroupReport(ageGroupEventId.value || undefined)
  ageGroupReport.value = res.data
}

onMounted(async () => {
  const [evRes, shRes, sumRes] = await Promise.all([
    getEvents(),
    getShooters(),
    getShootersSummary(),
  ])
  events.value = evRes.data
  shooters.value = shRes.data
  shootersSummary.value = sumRes.data
})
</script>
