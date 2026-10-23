import { ArrowRight, ArrowUpRight, CalendarDays, Coffee, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { EventSummary } from '../lib/api'

export function HomePage() {
  return (
    <main>
      <section className="hero-section">
        <div className="page-shell hero-layout">
          <div className="hero-copy">
            <span className="eyebrow hero-eyebrow">Business Networking &amp; Growth Community</span>
            <h1>Brew Connections. <em>Build Trust. Grow Business.</em></h1>
            <p>BNP CAFE brings entrepreneurs, professionals and business owners together to build trusted relationships, exchange meaningful referrals and grow.</p>
            <div className="hero-actions">
              <Link className="button button-primary hero-join-button" to="/membership">Join BNP CAFE</Link>
              <Link className="button button-accent hero-join-button" to="/chapters">Find a Chapter</Link>
              <Link className="button button-outline hero-join-button" to="/events">Explore Events</Link>
            </div>
            <p className="hero-proof">Connect Locally. Collaborate Meaningfully. Grow Beyond Your Network.</p>
          </div>
          <div className="hero-art">
            <img
              alt="Entrepreneurs networking over coffee"
              className="hero-business-image"
              src="/homepage-business.jpeg"
            />
          </div>
        </div>
      </section>

      <section aria-label="BNP CAFE community impact" className="home-stats">
        {[
          ['24+', 'Local chapters', '/chapters'],
          ['1,200+', 'Member businesses', '/directory'],
          ['9,500+', 'Referrals exchanged', '/stories'],
          ['300+', 'Events a year', '/events'],
        ].map(([value, label, route]) => (
          <Link className="home-stat" key={label} to={route}>
            <strong>{value}</strong>
            <span>{label}<ArrowRight size={14} /></span>
          </Link>
        ))}
      </section>

      <section className="intro-section section-pad" id="community">
        <div className="page-shell intro-grid">
          <div>
            <p className="eyebrow">Why BNP CAFE?</p>
            <h2>Networking should create business — not just business cards.</h2>
          </div>
          <div className="intro-copy">
            <p>We combine face-to-face relationships with a digital platform that keeps your network active between meetings.</p>
            <Link className="text-link" to="/about">Get to know us <ArrowUpRight size={16} /></Link>
          </div>
        </div>
        <div className="page-shell value-grid">
          {[
            ['Meet', 'Connect with business owners through local chapters and networking events.'],
            ['Know', 'Understand their businesses, capabilities, target customers and expertise.'],
            ['Trust', 'Build relationships through consistent interaction and meaningful conversations.'],
            ['Refer', 'Introduce trusted businesses to people who genuinely need them.'],
            ['Collaborate', 'Create partnerships, alliances, joint ventures and opportunities.'],
            ['Grow', 'Turn relationships into long-term business value.'],
          ].map(([title, description], index) => (
            <article className={`value-card${index === 1 || index === 4 ? ' value-card-highlight' : ''}`} key={title}>
              <span className="value-number">{String(index + 1).padStart(2, '0')}</span>
              <div className={`value-icon ${index % 3 === 0 ? 'value-icon-sage' : index % 3 === 1 ? 'value-icon-gold' : 'value-icon-rose'}`}>
                {index === 0 ? <Users size={22} /> : index === 1 ? <Coffee size={22} /> : <ArrowUpRight size={22} />}
              </div>
              <h3>{title}</h3>
              <p>{description}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="story-strip">
        <div className="page-shell story-layout">
          <div>
            <p className="eyebrow eyebrow-light">The BNP CAFE Difference</p>
            <h2>From networking to business growth.</h2>
            <p>Networking organisations, directories, social networks, event platforms and referral communities — brought together in one business-focused ecosystem.</p>
          </div>
          <div className="story-links">
            {[
              ['01', 'A Place to Meet.'],
              ['02', 'A Platform to Connect.'],
              ['03', 'A Community to Trust.'],
              ['04', 'A Network to Refer.'],
              ['05', 'An Ecosystem to Grow.'],
            ].map(([number, text]) => (
              <div className="story-link" key={number}><strong>{number}</strong><small>{text}</small></div>
            ))}
          </div>
        </div>
      </section>

      <section className="home-process section-pad">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">How it works</p>
            <h2>Your network. Your opportunities.</h2>
          </div>
        </div>
        <div className="page-shell home-step-grid">
          {[
            ['Discover', 'Find chapters, members and upcoming events in your city.'],
            ['Visit', 'Attend a meeting and experience the community first.'],
            ['Connect', 'Meet entrepreneurs and professionals from different industries.'],
            ['Build Your Profile', 'Share your products, services, expertise and ideal customers.'],
            ['Build Relationships', 'Move beyond introductions to genuine relationships.'],
            ['Find Referral Partners', 'Discover businesses serving complementary customers.'],
            ['Exchange Opportunities', 'Share referrals, introductions and partnerships.'],
            ['Grow', 'Turn your network into new conversations and new business.'],
          ].map(([title, description], index) => (
            <article className="home-step-card" key={title}>
              <span>{String(index + 1).padStart(2, '0')}</span>
              <h3>{title}</h3>
              <p>{description}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="home-audience section-pad">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">Built for business owners</p>
            <h2>Whether you are building, scaling or expanding — your network matters.</h2>
          </div>
        </div>
        <div className="page-shell home-audience-content">
          <div className="home-audience-tags">
            {['Entrepreneurs', 'Small & Medium Businesses', 'Professionals', 'Consultants & Service Providers', 'Startups', 'Established Businesses', 'Freelancers', 'Business Leaders'].map((audience) => (
              <span key={audience}>{audience}</span>
            ))}
          </div>
          <blockquote>Business networking isn't only about who you know. It's about who they know.</blockquote>
        </div>
      </section>

      <section className="home-network section-pad">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">Your business network starts here</p>
            <h2>Find the people who can help you grow.</h2>
          </div>
        </div>
        <div className="page-shell home-network-list">
          <p>Your next customer may be connected to someone you already know.</p>
          <p>Your next strategic partner may be sitting in another business category.</p>
          <p>Your next supplier may be part of your local business community.</p>
          <p>Your next opportunity may begin with one introduction.</p>
        </div>
      </section>

      <section className="home-testimonials section-pad">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">Success stories</p>
            <h2>Relationships that turned into results.</h2>
          </div>
        </div>
        <div className="page-shell home-testimonial-grid">
          {[
            ['Within three months my chapter introduced me to two of my biggest clients. The trust was already there before the first call.', 'Priya S.', 'Chartered Accountant, Mumbai'],
            ["I found my logistics partner, my lawyer and my web agency through BNP CAFE. It's like having a business family.", 'Arjun M.', 'Interior Design Studio, Pune'],
            ['The workshops made me a better networker. I now know exactly how to ask for the introductions I need.', 'Sana Q.', 'Events Company, Hyderabad'],
          ].map(([quote, name, role]) => (
            <figure className="home-testimonial-card" key={name}>
              <span aria-hidden="true">“</span>
              <blockquote>{quote}</blockquote>
              <figcaption><strong>{name}</strong><small>{role}</small></figcaption>
            </figure>
          ))}
        </div>
        <div className="page-shell home-testimonial-links">
          <Link className="text-link" to="/stories">Read all success stories <ArrowRight size={16} /></Link>
          <Link className="text-link" to="/events#guest-pass">Get a free guest pass <ArrowRight size={16} /></Link>
        </div>
      </section>

      <section className="home-join section-pad">
        <div className="page-shell section-heading">
          <div>
            <p className="eyebrow">Why people join</p>
            <h2>What are you looking for?</h2>
          </div>
        </div>
        <div className="page-shell home-join-grid">
          {[
            ['“I Want More Customers.”', 'Build relationships with businesses that can introduce you to potential customers.'],
            ['“I Need Reliable Referral Partners.”', 'Find complementary businesses serving similar customer groups.'],
            ['“I Want to Expand My Network.”', 'Meet entrepreneurs and professionals outside your existing circle.'],
            ['“I Want Business Advice.”', 'Connect with people who have faced similar challenges.'],
            ['“I Want to Enter a New Market.”', 'Discover businesses and opportunities in new locations.'],
            ['“I Want Strategic Partnerships.”', 'Meet businesses with complementary capabilities and audiences.'],
          ].map(([title, description]) => (
            <article className="home-join-card" key={title}><h3>{title}</h3><p>{description}</p></article>
          ))}
        </div>
      </section>

      <section className="home-promise">
        <div className="page-shell">
          <p className="eyebrow">The BNP CAFE Promise</p>
          <h2>We don't promise every connection will become business.</h2>
          <p>We promise something more meaningful: a better environment to build the relationships that can create business.</p>
        </div>
      </section>

      <section className="membership-cta">
        <div className="page-shell membership-cta-inner">
          <div>
            <h2>Your Next Customer Could Be One Introduction Away.</h2>
            <p>Don't just build a business. Build the network behind it.</p>
          </div>
          <div className="cta-right home-final-actions">
            <Link className="button button-primary" to="/membership">Join BNP CAFE <ArrowRight size={16} /></Link>
            <Link className="button button-accent" to="/chapters">Find a Chapter <ArrowRight size={16} /></Link>
            <Link className="button button-outline" to="/events#upcoming-events">Attend an Event <ArrowRight size={16} /></Link>
          </div>
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
