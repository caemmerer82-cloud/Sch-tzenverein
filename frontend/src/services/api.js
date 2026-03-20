import axios from 'axios'

const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL: apiUrl,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Events
export const getEvents = () => api.get('/events')
export const getEvent = (id) => api.get(`/events/${id}`)
export const createEvent = (data) => api.post('/events', data)
export const updateEvent = (id, data) => api.put(`/events/${id}`, data)
export const deleteEvent = (id) => api.delete(`/events/${id}`)

// Event Dates
export const getEventDates = (eventId) => api.get(`/events/${eventId}/dates`)
export const addEventDate = (eventId, data) => api.post(`/events/${eventId}/dates`, data)
export const deleteEventDate = (eventId, dateId) => api.delete(`/events/${eventId}/dates/${dateId}`)

// Event Participants
export const addParticipant = (eventId, shooterId) => api.post(`/events/${eventId}/participants`, { shooter_id: shooterId })
export const removeParticipant = (eventId, shooterId) => api.delete(`/events/${eventId}/participants/${shooterId}`)

// Shooters
export const getShooters = (params) => api.get('/shooters', { params })
export const getShooter = (id) => api.get(`/shooters/${id}`)
export const createShooter = (data) => api.post('/shooters', data)
export const updateShooter = (id, data) => api.put(`/shooters/${id}`, data)
export const deleteShooter = (id) => api.delete(`/shooters/${id}`)

// Scores
export const getScores = (params) => api.get('/scores', { params })
export const createScore = (data) => api.post('/scores', data)
export const updateScore = (id, data) => api.put(`/scores/${id}`, data)
export const deleteScore = (id) => api.delete(`/scores/${id}`)

// Timeslots
export const getTimeslots = (params) => api.get('/timeslots', { params })

// Reservations
export const getReservations = (params) => api.get('/reservations', { params })
export const createReservation = (data) => api.post('/reservations', data)
export const deleteReservation = (id) => api.delete(`/reservations/${id}`)

// Age Groups
export const getAgeGroups = () => api.get('/age-groups')
export const createAgeGroup = (data) => api.post('/age-groups', data)
export const updateAgeGroup = (id, data) => api.put(`/age-groups/${id}`, data)
export const deleteAgeGroup = (id) => api.delete(`/age-groups/${id}`)

// Reports
export const getEventReport = (eventId) => api.get('/reports/event', { params: { event_id: eventId } })
export const getShooterReport = (shooterId) => api.get('/reports/shooter', { params: { shooter_id: shooterId } })
export const getShootersSummary = () => api.get('/reports/shooter')
export const getAgeGroupReport = (eventId) => api.get('/reports/age-group', { params: { event_id: eventId } })

export default api
