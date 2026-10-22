import { useEffect, useState } from 'react'
import { ArrowLeft, CalendarDays, Check, MapPin, Plus, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage, ChapterSummary, EventSummary } from '../lib/api'

interface EventForm {
  chapter_id: string
  title: string
  description: string
  venue: string
  starts_at: string
  ends_at: string
  registration_closes_at: string
  capacity: string
  status: 'draft' | 'published' | 'cancelled'
}

interface Registration {
  id: number
  event_id: number
  name: string
  email: string
  phone: string | null
  business_name: string | null
  status: string
  attended_at: string | null
  created_at: string | null
}

interface AdminEventsPageProps {
  onNotice: (message: string) => void
}

const emptyForm: EventForm = {
  chapter_id: '',
  title: '',
  description: '',
  venue: '',
  starts_at: '',
  ends_at: '',
  registration_closes_at: '',
  capacity: '',
  status: 'draft',
}

export function AdminEventsPage({ onNotice }: AdminEventsPageProps) {
  const { user } = useAuth()
  const [events, setEvents] = useState<EventSummary[]>([])
  const [chapters, setChapters] = useState<ChapterSummary[]>([])
  const [form, setForm] = useState<EventForm>(emptyForm)
  const [registrations, setRegistrations] = useState<Record<number, Registration[]>>({})
  const [expandedEvent, setExpandedEvent] = useState<number | null>(null)
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [savingRegistration, setSavingRegistration] = useState<number | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    Promise.all([
      api.get<ApiPage<EventSummary>>('/api/admin/events', { params: { per_page: 100 } }),
      api.get<ApiPage<ChapterSummary>>('/api/admin/chapters', { params: { per_page: 100 } }),
    ])
      .then(([eventResponse, chapterResponse]) => {
        if (!active) return
        setEvents(eventResponse.data.data)
        setChapters(chapterResponse.data.data)
      })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Events could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function createEvent(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      const response = await api.post<{ data: EventSummary }>('/api/admin/events', {
        ...form,
        chapter_id: form.chapter_id ? Number(form.chapter_id) : null,
        capacity: form.capacity ? Number(form.capacity) : null,
        registration_closes_at: form.registration_closes_at || null,
      })
      setEvents((current) => [response.data.data, ...current])
      setForm(emptyForm)
      onNotice('Your event has been created.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'The event could not be created.'))
    } finally {
      setSubmitting(false)
    }
  }

  async function loadRegistrations(event: EventSummary) {
    setError('')
    if (expandedEvent === event.id) {
      setExpandedEvent(null)
      return
    }
    setExpandedEvent(event.id)
    try {
      const response = await api.get<ApiPage<Registration>>(`/api/admin/events/${event.id}/registrations`)
      setRegistrations((current) => ({ ...current, [event.id]: response.data.data }))
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Event registrations could not be loaded.'))
    }
  }

  async function toggleAttendance(event: EventSummary, registration: Registration) {
    setError('')
    setSavingRegistration(registration.id)
    try {
      const response = await api.patch<{ data: Registration }>(
        `/api/admin/events/${event.id}/registrations/${registration.id}/attendance`,
        { attended: !registration.attended_at },
      )
      setRegistrations((current) => ({
        ...current,
        [event.id]: current[event.id].map((item) => item.id === registration.id ? response.data.data : item),
      }))
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Attendance could not be updated.'))
    } finally {
      setSavingRegistration(null)
    }
  }

  const canCreateEvents = user?.role === 'super_admin'

  return (
    <main className="page-shell page-content admin-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to dashboard</Link>
      <div className="admin-page-heading"><div><p className="eyebrow">Chapter operations</p><h1>Events &amp; <em>attendance.</em></h1><p>Manage public gatherings and check in registered guests.</p></div><span className="admin-queue-count"><CalendarDays size={17} /> {events.length} events</span></div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {canCreateEvents && (
        <details className="admin-create-panel">
          <summary className="button button-primary"><Plus size={16} /> Create an event</summary>
          <form className="form-card admin-event-form" onSubmit={createEvent}>
            <label className="field-label">Event title<input maxLength={180} onChange={(e) => setForm({ ...form, title: e.target.value })} required value={form.title} /></label>
            <div className="form-row">
              <label className="field-label">Chapter<select onChange={(e) => setForm({ ...form, chapter_id: e.target.value })} value={form.chapter_id}><option value="">Platform-wide event</option>{chapters.map((chapter) => <option key={chapter.id} value={chapter.id}>{chapter.name}</option>)}</select></label>
              <label className="field-label">Venue<input onChange={(e) => setForm({ ...form, venue: e.target.value })} required value={form.venue} /></label>
            </div>
            <label className="field-label">Description<textarea maxLength={5000} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={3} value={form.description} /></label>
            <div className="form-row">
              <label className="field-label">Starts at<input onChange={(e) => setForm({ ...form, starts_at: e.target.value })} required type="datetime-local" value={form.starts_at} /></label>
              <label className="field-label">Ends at<input onChange={(e) => setForm({ ...form, ends_at: e.target.value })} required type="datetime-local" value={form.ends_at} /></label>
            </div>
            <div className="form-row">
              <label className="field-label">Registration closes <span className="field-optional">Optional</span><input onChange={(e) => setForm({ ...form, registration_closes_at: e.target.value })} type="datetime-local" value={form.registration_closes_at} /></label>
              <label className="field-label">Capacity <span className="field-optional">Optional</span><input min="1" onChange={(e) => setForm({ ...form, capacity: e.target.value })} type="number" value={form.capacity} /></label>
            </div>
            <label className="field-label">Initial status<select onChange={(e) => setForm({ ...form, status: e.target.value as EventForm['status'] })} value={form.status}><option value="draft">Draft</option><option value="published">Published</option></select></label>
            <button className="button button-primary" disabled={submitting} type="submit">{submitting ? 'Creating…' : 'Save event'}</button>
          </form>
        </details>
      )}
      <div className="admin-event-list">
        {loading ? <p className="loading-copy">Loading events…</p> : events.length ? events.map((event) => (
          <article className="admin-event-card" key={event.id}>
            <div className="admin-event-main"><span className={`status-pill status-${event.status}`}>{humanize(event.status)}</span><h2>{event.title}</h2><p><CalendarDays size={14} /> {event.starts_at ? formatDate(event.starts_at) : 'Date to be announced'}</p><p><MapPin size={14} /> {event.venue || 'Venue to be announced'} · {event.chapter?.name || 'Platform-wide'}</p></div>
            <button className="button button-outline-dark" onClick={() => void loadRegistrations(event)} type="button"><Users size={15} /> {expandedEvent === event.id ? 'Hide guests' : 'Registrations'}</button>
            {expandedEvent === event.id && (
              <div className="event-registration-list">
                {(registrations[event.id] ?? []).length ? (registrations[event.id] ?? []).map((registration) => (
                  <div className="registration-row" key={registration.id}><span><strong>{registration.name}</strong><small>{registration.email}{registration.business_name ? ` · ${registration.business_name}` : ''}</small></span>{registration.status === 'cancelled' ? <span className="status-pill status-cancelled">Cancelled</span> : <button className={registration.attended_at ? 'attendance-button attended' : 'attendance-button'} disabled={savingRegistration === registration.id} onClick={() => void toggleAttendance(event, registration)} type="button">{registration.attended_at ? <><Check size={13} /> Attended</> : 'Mark attended'}</button>}</div>
                )) : <p className="empty-inline">No registrations are available for this event.</p>}
              </div>
            )}
          </article>
        )) : <div className="empty-panel"><CalendarDays size={22} /><p>No events have been created.</p><span>Create an event to publish it for the community.</span></div>}
      </div>
    </main>
  )
}

function formatDate(value: string) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Date to be announced' : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(date)
}

function humanize(value: string) {
  return value.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}
