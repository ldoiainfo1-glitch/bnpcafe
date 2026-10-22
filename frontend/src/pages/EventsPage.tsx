import { useEffect, useState } from 'react'
import { CalendarDays, MapPin } from 'lucide-react'
import { api, apiErrorMessage, prepareSessionRequest } from '../lib/api'
import type { ApiPage, EventRegistrationInput, EventSummary } from '../lib/api'
import { EventCard } from './HomePage'

interface EventsPageProps {
  onNotice: (message: string) => void
}

export function EventsPage({ onNotice }: EventsPageProps) {
  const [events, setEvents] = useState<EventSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<EventSummary>>('/api/events', { params: { per_page: 50 } })
      .then((response) => { if (active) setEvents(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'We could not load events. Please try again later.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  return (
    <main className="page-shell page-content">
      <div className="page-intro">
        <p className="eyebrow">Make time for good connections</p>
        <h1>Meet, learn, <em>and grow.</em></h1>
        <p>Come along to a local gathering and get to know the people behind the businesses in your community.</p>
      </div>
      {error && <p className="inline-error" role="status">{error}</p>}
      {loading ? (
        <p className="loading-copy" role="status">Finding the next gathering…</p>
      ) : events.length ? (
        <div className="event-list">
          {events.map((event) => (
            <article className="event-row" id={`event-${event.id}`} key={event.id}>
              <div className="event-row-details"><EventCard event={event} /></div>
              <div className="event-row-extra">
                <p><CalendarDays size={16} />{formatEventDate(event.starts_at)}</p>
                {event.venue && <p><MapPin size={16} />{event.venue}</p>}
                <details className="register-details">
                  <summary className="button button-primary">Save your spot</summary>
                  <EventRegistrationForm event={event} onNotice={onNotice} />
                </details>
              </div>
            </article>
          ))}
        </div>
      ) : (
        <div className="empty-panel"><CalendarDays size={24} /><p>There are no upcoming events just yet.</p><span>Check back soon, or get in touch to learn about the next gathering.</span></div>
      )}
    </main>
  )
}

function EventRegistrationForm({ event, onNotice }: { event: EventSummary; onNotice: (message: string) => void }) {
  const [form, setForm] = useState<EventRegistrationInput>({ name: '', email: '', phone: '', business_name: '' })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function submit(eventObject: React.FormEvent<HTMLFormElement>) {
    eventObject.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await prepareSessionRequest()
      await api.post(`/api/events/${event.id}/registrations`, form)
      onNotice('You’re registered. We look forward to meeting you!')
      setForm({ name: '', email: '', phone: '', business_name: '' })
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your registration could not be completed. Please try again.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="form-card register-form" onSubmit={submit}>
      <h3>Register for {event.title}</h3>
      {error && <p className="inline-error" role="alert">{error}</p>}
      <label className="field-label">Your name<input autoComplete="name" onChange={(e) => setForm({ ...form, name: e.target.value })} required value={form.name} /></label>
      <label className="field-label">Email address<input autoComplete="email" onChange={(e) => setForm({ ...form, email: e.target.value })} required type="email" value={form.email} /></label>
      <label className="field-label">Phone <span className="field-optional">Optional</span><input autoComplete="tel" onChange={(e) => setForm({ ...form, phone: e.target.value })} value={form.phone} /></label>
      <label className="field-label">Business name <span className="field-optional">Optional</span><input autoComplete="organization" onChange={(e) => setForm({ ...form, business_name: e.target.value })} value={form.business_name} /></label>
      <button className="button button-primary" disabled={submitting} type="submit">{submitting ? 'Registering…' : 'Confirm registration'}</button>
    </form>
  )
}

function formatEventDate(value: string | null) {
  if (!value) return 'Date to be announced'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Date to be announced'
    : new Intl.DateTimeFormat(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).format(date)
}
