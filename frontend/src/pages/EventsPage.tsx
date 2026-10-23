import { useEffect, useLayoutEffect, useState } from 'react'
import { ArrowRight, CalendarDays, Check } from 'lucide-react'
import { Link, useLocation } from 'react-router-dom'
import { api, apiErrorMessage, prepareSessionRequest } from '../lib/api'
import type { ApiPage, EventRegistrationInput, EventSummary } from '../lib/api'
import { MarketingCta } from './PublicInformationPages'

interface EventsPageProps {
  onNotice: (message: string) => void
}

const featuredEvents = [
  { date: 'Nov 04', type: 'Chapter Meeting · Mumbai', title: 'Mumbai Central Chapter Meeting' },
  { date: 'Nov 12', type: 'Masterclass · Online', title: 'Referral Marketing Masterclass' },
  { date: 'Nov 19', type: 'Business Mixer · Bengaluru', title: "Founders' Evening Mixer" },
  { date: 'Nov 27', type: 'Inter-City · Pune', title: 'Inter-City Networking Day' },
]
const eventFormats = [
  ['Chapter Meetings', 'Regular local networking opportunities.'],
  ['Networking Events', 'Meet entrepreneurs across industries.'],
  ['Workshops', 'Learn practical business skills.'],
  ['Masterclasses', 'Insights from experienced professionals.'],
  ['Business Mixers', 'Informal relationships, relaxed setting.'],
  ['Inter-City Networking', 'Connect beyond your local chapter.'],
  ['Special Events', 'Celebrate members and milestones.'],
]
const guestPasses = [
  { id: 'pass-first-visit', tag: 'Free', title: 'First Visit Pass', benefits: ['One chapter meeting', 'Guest introduction slot', 'Meet chapter members'], action: 'Claim this pass' },
  { id: 'pass-explorer', tag: 'Most popular', title: 'Explorer Pass', benefits: ['Two chapter meetings', 'One business mixer', 'Guest profile & event tracking', 'Membership consultation'], action: 'Claim this pass' },
  { id: 'pass-business', tag: 'For teams', title: 'Business Guest Pass', benefits: ['Bring up to 3 colleagues', 'One workshop or masterclass', 'Partnership conversation'], action: 'Claim this pass' },
]

export function EventsPage({ onNotice }: EventsPageProps) {
  const [events, setEvents] = useState<EventSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const { hash } = useLocation()

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

  useLayoutEffect(() => {
    if (!hash) return

    const target = document.getElementById(hash.slice(1))
    if (!target) return

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const headerHeight = document.querySelector('.site-header')?.getBoundingClientRect().height ?? 0
    const targetTop = target.getBoundingClientRect().top + window.scrollY - headerHeight - 16
    window.scrollTo({ top: targetTop, behavior: reduceMotion ? 'instant' : 'smooth' })

    if (hash !== '#upcoming-events') return

    target.classList.add('events-arrival-target')
    document.getElementById('upcoming-events-heading')?.focus({ preventScroll: true })
    const timeoutId = window.setTimeout(() => target.classList.remove('events-arrival-target'), 1400)

    return () => {
      window.clearTimeout(timeoutId)
      document.getElementById(hash.slice(1))?.classList.remove('events-arrival-target')
    }
  }, [hash])

  return (
    <main className="events-page">
      <section className="events-hero">
        <div className="events-container">
          <p className="eyebrow">Networking Events</p>
          <h1>Don't attend events. Build relationships.</h1>
          <p>BNP CAFE events create meaningful business interactions — not crowds exchanging business cards.</p>
        </div>
      </section>
      <section className="events-upcoming" id="upcoming-events">
        <div className="events-container">
          <div className="events-section-heading">
            <h2 id="upcoming-events-heading" tabIndex={-1}>Upcoming events</h2>
            {loading && <span role="status">Finding the next gathering…</span>}
            {error && <span className="events-load-error" role="status">{error}</span>}
          </div>
          <div className="events-upcoming-list">
            {events.length ? events.map((event) => (
              <article className="events-upcoming-card" id={`event-${event.id}`} key={event.id}>
                <time>{formatShortDate(event.starts_at)}</time>
                <div>
                  <p>{event.chapter?.name || 'BNP CAFE Event'}{event.venue ? ` · ${event.venue}` : ''}</p>
                  <h3>{event.title}</h3>
                  <span>{event.description}</span>
                </div>
                <details className="event-quick-register">
                  <summary>Register as guest <ArrowRight size={15} /></summary>
                  <EventRegistrationForm event={event} onNotice={onNotice} />
                </details>
              </article>
            )) : featuredEvents.map((event) => (
              <article className="events-upcoming-card" key={event.title}>
                <time>{event.date}</time>
                <div>
                  <p>{event.type}</p>
                  <h3>{event.title}</h3>
                </div>
                <Link to="/events#guest-pass">Register as guest <ArrowRight size={15} /></Link>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="events-formats">
        <div className="events-container">
          <div className="events-section-heading"><h2>Explore event formats</h2></div>
          <div className="event-format-grid">
            {eventFormats.map(([title, description]) => (
              <article className="event-format-card" key={title}>
                <span><CalendarDays aria-hidden="true" size={19} /></span>
                <h3>{title}</h3>
                <p>{description}</p>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="event-passes" id="guest-pass">
        <div className="events-container">
          <div className="events-section-heading">
            <div>
              <p className="eyebrow">Guest event passes</p>
              <h2>Try BNP CAFE before you join.</h2>
              <p>Every member success story started with a first visit. Pick the pass that fits you.</p>
            </div>
          </div>
          <div className="event-pass-grid">
            {guestPasses.map((pass) => (
              <article className={`event-pass-card${pass.id === 'pass-explorer' ? ' event-pass-featured' : ''}`} id={pass.id} key={pass.id}>
                <p className="event-pass-tag">{pass.tag}</p>
                <h3>{pass.title}</h3>
                <ul>{pass.benefits.map((benefit) => <li key={benefit}><Check aria-hidden="true" size={16} />{benefit}</li>)}</ul>
                <Link className="button button-primary" to="/membership">{pass.action} <ArrowRight size={15} /></Link>
              </article>
            ))}
          </div>
          <p className="event-pass-note">Wondering what a visit can lead to? <Link to="/stories">Read member success stories</Link>.</p>
        </div>
      </section>
      <section className="events-agenda">
        <div className="events-container">
          <div className="events-section-heading">
            <div>
              <p className="eyebrow">What to expect</p>
              <h2>Your first chapter meeting, minute by minute.</h2>
            </div>
          </div>
          <ol className="events-agenda-list">
            {[
              ['7:00', 'Arrive & open networking over coffee'],
              ['7:20', 'Guest welcome & member introductions'],
              ['7:45', 'Business spotlights & referral exchange'],
              ['8:30', 'One-to-one conversations with members'],
            ].map(([time, item]) => <li key={time}><time>{time}</time><span>{item}</span></li>)}
          </ol>
        </div>
      </section>
      <section className="events-guest-invite">
        <div className="events-container">
          <p className="eyebrow">Your first experience</p>
          <h2>Curious? Come as a guest. Leave with connections.</h2>
          <p>You don't have to commit before you understand the community. Find an event, register as a guest, meet the members and decide whether membership is right for you.</p>
          <Link className="button button-accent" to="/events#guest-pass">Visit an Event <ArrowRight size={16} /></Link>
        </div>
      </section>
      <MarketingCta />
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

function formatShortDate(value: string | null) {
  if (!value) return 'Date TBA'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Date TBA'
    : new Intl.DateTimeFormat(undefined, { month: 'short', day: '2-digit' }).format(date)
}
