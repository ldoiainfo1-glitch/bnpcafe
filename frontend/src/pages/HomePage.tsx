import { useEffect, useState } from 'react'
import { ArrowRight, ArrowUpRight, CalendarDays, Coffee, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage, ApiItem, ContentSummary, EventSummary, MembershipPlan, SiteSettings } from '../lib/api'

export function HomePage() {
  const [events, setEvents] = useState<EventSummary[]>([])
  const [plans, setPlans] = useState<MembershipPlan[]>([])
  const [pages, setPages] = useState<ContentSummary[]>([])
  const [settings, setSettings] = useState<SiteSettings>({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    async function load() {
      try {
        const [eventResponse, planResponse, pageResponse, settingResponse] = await Promise.all([
          api.get<ApiPage<EventSummary>>('/api/events', { params: { per_page: 3 } }),
          api.get<ApiPage<MembershipPlan>>('/api/membership-plans', { params: { per_page: 3 } }),
          api.get<ApiPage<ContentSummary>>('/api/content/pages'),
          api.get<ApiItem<SiteSettings>>('/api/settings'),
        ])
        if (active) {
          setEvents(eventResponse.data.data.slice(0, 3))
          setPlans(planResponse.data.data.slice(0, 3))
          setPages(pageResponse.data.data.slice(0, 4))
          setSettings(settingResponse.data.data)
        }
      } catch (requestError) {
        if (active) {
          setError(apiErrorMessage(requestError, 'Our latest updates are not available right now.'))
        }
      } finally {
        if (active) setLoading(false)
      }
    }
    void load()
    return () => { active = false }
  }, [])

  return (
    <main>
      <section className="hero-section">
        <div className="page-shell hero-layout">
          <div className="hero-copy">
            <span className="eyebrow hero-eyebrow">India's Growing Business Networking Community</span>
            <h1>Brew Real Connections.<br /><em>Build Real Business.</em></h1>
            <p>
              {settings.home_announcement?.trim()
                || 'BNP Cafe brings entrepreneurs and business owners together through structured referral networking, local pincodes, and events — so every cup of coffee turns into a conversation worth having.'}
            </p>
            <div className="hero-actions">
              <Link className="button button-primary hero-join-button" to="/membership">Join BNP Espresso</Link>
              <Link className="button button-accent hero-join-button" to="/membership">Join BNP Roastery</Link>
            </div>
          </div>
          <div className="hero-art">
            <img
              alt="Business owners connecting and building professional networks"
              className="hero-business-image"
              src="/homepage-business.jpeg"
            />
          </div>
        </div>
      </section>

      <section className="intro-section section-pad" id="community">
        <div className="page-shell intro-grid">
          <div>
            <p className="eyebrow">More than a business card</p>
            <h2>Build the kind of network<br />that <em>shows up.</em></h2>
          </div>
          <div className="intro-copy">
            <p>BNP Cafe brings business owners together to make useful connections, learn from one another, and find more opportunities to grow.</p>
            <Link className="text-link" to="/pages/about">Get to know us <ArrowUpRight size={16} /></Link>
          </div>
        </div>
        <div className="page-shell value-grid">
          <article className="value-card">
            <span className="value-number">01</span>
            <div className="value-icon value-icon-sage"><Users size={22} /></div>
            <h3>People first</h3>
            <p>Meet thoughtful local business owners who believe that strong relationships make stronger businesses.</p>
          </article>
          <article className="value-card value-card-highlight">
            <span className="value-number">02</span>
            <div className="value-icon value-icon-gold"><Coffee size={22} /></div>
            <h3>Learn together</h3>
            <p>Trade practical ideas, share experience, and leave every conversation with something useful.</p>
          </article>
          <article className="value-card">
            <span className="value-number">03</span>
            <div className="value-icon value-icon-rose"><ArrowUpRight size={22} /></div>
            <h3>Grow with purpose</h3>
            <p>Turn genuine connections into collaborations, referrals, and opportunities to move forward.</p>
          </article>
        </div>
      </section>

      {pages.length > 0 && (
        <section className="story-strip">
          <div className="page-shell story-layout">
            <div>
              <p className="eyebrow eyebrow-light">Explore BNP Cafe</p>
              <h2>Good things grow<br />when we <em>grow together.</em></h2>
            </div>
            <div className="story-links">
              {pages.map((page) => (
                <Link className="story-link" key={page.id} to={`/pages/${page.slug}`}>
                  <span><strong>{page.title}</strong><small>{page.summary || 'Discover more about our community.'}</small></span>
                  <ArrowUpRight size={18} />
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      <section className="section-pad events-preview">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">Come say hello</p>
            <h2>Meet us <em>in person.</em></h2>
          </div>
          <Link className="text-link" to="/events">All upcoming events <ArrowRight size={16} /></Link>
        </div>
        <div className="page-shell">
          {error && <p className="inline-error" role="status">{error}</p>}
          {loading ? (
            <p className="loading-copy" role="status">Loading the latest from BNP Cafe…</p>
          ) : events.length ? (
            <div className="event-grid">
              {events.map((event) => <EventCard event={event} key={event.id} />)}
            </div>
          ) : (
            <div className="empty-panel"><CalendarDays size={23} /><p>New gatherings are on the way. Check back soon.</p></div>
          )}
        </div>
      </section>

      <section className="membership-cta">
        <div className="page-shell membership-cta-inner">
          <div>
            <p className="eyebrow eyebrow-light">Your next introduction could change everything</p>
            <h2>There’s a seat<br />waiting <em>for you.</em></h2>
            <p>Find a membership that fits where you are today—and where you want your business to go.</p>
          </div>
          <div className="cta-right">
            <div className="cta-plan-count"><strong>{plans.length || 'A'}</strong><span>{plans.length === 1 ? 'membership option' : 'ways to belong'}</span></div>
            <Link className="button button-accent" to="/membership">Discover membership <ArrowRight size={17} /></Link>
          </div>
          <span aria-hidden="true" className="cta-decoration">BNP</span>
        </div>
      </section>
    </main>
  )
}

export function EventCard({ event }: { event: EventSummary }) {
  const date = event.starts_at ? new Date(event.starts_at) : null
  const dateText = date && !Number.isNaN(date.getTime())
    ? new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
    : 'Date to be announced'

  return (
    <article className="event-card">
      <div className="event-card-top">
        <span className="event-date-icon"><CalendarDays size={19} /></span>
        <span className="event-date">{dateText}</span>
        <span className="event-type">{event.chapter?.name || 'BNP Cafe'}</span>
      </div>
      <h3>{event.title}</h3>
      <p>{event.description}</p>
      <Link className="text-link event-link" to={`/events#event-${event.id}`}>
        Event details <ArrowRight size={15} />
      </Link>
    </article>
  )
}
