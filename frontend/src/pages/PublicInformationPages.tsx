import { useEffect, useState } from 'react'
import { ArrowRight, ArrowUpRight, BookOpenText, Building2, CalendarDays, Check, MapPin, MessageSquareQuote, Network, Search, Users } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage, ContentSummary } from '../lib/api'

export function AboutPage() {
  const features: [LucideIcon, string, string][] = [
    [MapPin, 'Local Business Chapters', 'Build strong relationships in your city and community.'],
    [Network, 'Digital Business Network', 'Stay connected with members beyond the physical meeting.'],
    [Building2, 'Business Directory', 'Discover businesses by name, category, location and expertise.'],
    [Users, 'Referral Network', 'Find referral partners and create warm introductions.'],
    [CalendarDays, 'Business Events', 'Networking meetings, workshops and learning sessions.'],
    [MessageSquareQuote, 'Business Community', 'Ask questions, share knowledge and support each other.'],
    [BookOpenText, 'Business Learning', 'Sharpen networking, sales and leadership skills.'],
    [Users, 'Member Profiles', 'Tell members who you are, what you do and who you help.'],
  ]
  const connections = [
    'Customers',
    'Referral partners',
    'Strategic partners',
    'Suppliers',
    'Advisors',
    'Collaborators',
    'Service providers',
    'Business allies',
  ]
  const topics = [
    'Business networking',
    'Referral marketing',
    'Sales',
    'Personal branding',
    'Public speaking',
    'Leadership',
    'Digital marketing',
    'Customer acquisition',
    'Negotiation',
    'Communication',
    'Entrepreneurship',
  ]

  return (
    <main className="about-page">
      <section className="about-hero">
        <div className="about-container">
          <p className="eyebrow">What is BNP CAFE?</p>
          <h1>More than a networking group.</h1>
          <p>BNP CAFE is a structured business networking and referral ecosystem built for today's business owner.</p>
        </div>
      </section>
      <section className="about-features">
        <div className="about-container">
          <div className="about-section-heading">
            <h2>One platform, eight ways to grow.</h2>
          </div>
          <div className="about-feature-grid">
            {features.map(([Icon, title, description]) => (
              <article className="about-feature-card" key={title}>
                <span className="about-feature-icon"><Icon aria-hidden="true" size={21} /></span>
                <span>
                  <h3>{title}</h3>
                  <p>{description}</p>
                </span>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="about-connections">
        <div className="about-container">
          <div className="about-section-heading">
            <p className="eyebrow">Smart Connections</p>
            <h2>Don't just grow your network. Grow the right network.</h2>
            <p>Thousands of connections do not automatically create business. The right connections do.</p>
          </div>
          <ul className="about-chip-grid" aria-label="Connections you can build">
            {connections.map((connection) => <li key={connection}>{connection}</li>)}
          </ul>
        </div>
      </section>
      <section className="about-trust">
        <div className="about-container">
          <p className="eyebrow">Recommendations &amp; Trust</p>
          <h2>Reputation builds relationships.</h2>
          <p>Build credibility through your professional presence, community participation and member recommendations. Trust is the foundation of business growth.</p>
        </div>
      </section>
      <section className="about-learning">
        <div className="about-container">
          <div className="about-section-heading">
            <p className="eyebrow">Business Learning</p>
            <h2>Become better at business — while building your network.</h2>
          </div>
          <ul className="about-topic-grid">
            {topics.map((topic) => <li key={topic}>{topic}</li>)}
          </ul>
        </div>
      </section>
      <section className="about-community">
        <div className="about-container">
          <p className="eyebrow">Community</p>
          <h2>When businesses help businesses, communities grow.</h2>
          <p>People to ask. People to learn from. People to collaborate with. People who can open a door.</p>
        </div>
      </section>
      <section className="about-cta">
        <div className="about-container about-cta-inner">
          <div>
            <h2>Your Next Customer Could Be One Introduction Away.</h2>
            <p>Don't just build a business. Build the network behind it.</p>
          </div>
          <div className="about-cta-actions">
            <Link className="button button-primary" to="/membership">Join BNP CAFE <ArrowRight size={16} /></Link>
            <Link className="button button-accent" to="/chapters">Find a Chapter <ArrowRight size={16} /></Link>
            <Link className="button button-outline" to="/events#upcoming-events">Attend an Event <ArrowRight size={16} /></Link>
          </div>
        </div>
      </section>
    </main>
  )
}

export function BlogPage() {
  const [articles, setArticles] = useState<ContentSummary[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<ContentSummary>>('/api/content/pages', { params: { per_page: 50 } })
      .then((response) => {
        if (active) setArticles(response.data.data.filter((page) => page.slug.startsWith('blog-')))
      })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Articles are unavailable at the moment.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  return (
    <main className="page-shell page-content public-info-page">
      <div className="page-intro">
        <p className="eyebrow">Ideas for business and community</p>
        <h1>Notes from the <em>network.</em></h1>
        <p>Explore practical perspectives on building relationships, learning from one another, and growing a business with community.</p>
      </div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {loading ? <p className="loading-copy" role="status">Loading articles…</p> : articles.length ? (
        <section className="public-article-grid" aria-label="Published articles">
          {articles.map((article) => (
            <article className="public-article-card" key={article.id}>
              <span className="public-info-icon"><BookOpenText size={21} /></span>
              <p className="eyebrow">BNP Cafe Journal</p>
              <h2>{article.title}</h2>
              <p>{article.summary || article.body}</p>
              <Link className="text-link" to={`/pages/${article.slug}`}>Read article <ArrowRight size={15} /></Link>
            </article>
          ))}
        </section>
      ) : (
        <div className="public-info-empty">
          <span className="public-info-icon"><BookOpenText size={24} /></span>
          <h2>Fresh perspectives are on the way.</h2>
          <p>Our journal is being prepared. In the meantime, join a gathering and learn directly from local business owners.</p>
          <Link className="button button-primary" to="/events">Explore upcoming events <ArrowRight size={16} /></Link>
        </div>
      )}
    </main>
  )
}

export function TestimonialsPage() {
  const metrics = [
    ['9,500+', 'Referrals exchanged', 'Warm introductions passed between members last year.'],
    ['1,200+', 'Member businesses', 'Across 60+ business categories.'],
    ['68%', 'Guests who return', 'Most first-time visitors come back for a second meeting.'],
    ['24+', 'Local chapters', 'Meeting weekly in cities across the country.'],
  ]
  const stories = [
    {
      metric: '2 anchor clients in 3 months',
      quote: '“A fellow member who runs a logistics firm introduced me to two of his partners. Both became retainer clients — the trust was already there before we met.”',
      name: 'Priya Sharma',
      company: 'Ledgerline Accounting · Mumbai',
      started: 'Visited as a guest on a First Visit Pass',
      action: 'Start with the free First Visit Pass',
      pass: 'First Visit Pass · Free, one chapter meeting',
      anchor: 'pass-first-visit',
    },
    {
      metric: '40% of new projects from referrals',
      quote: '“I found my lawyer, my supplier and my web agency inside the chapter. Now almost half my new projects come through member introductions.”',
      name: 'Arjun Mehta',
      company: 'Brightside Interiors · Pune',
      started: 'Joined after an Explorer Pass',
      action: 'Explore with the Explorer Pass',
      pass: 'Explorer Pass · Two meetings + a mixer',
      anchor: 'pass-explorer',
    },
    {
      metric: 'Expanded into 2 new cities',
      quote: '“Inter-city networking connected me with planners in Bengaluru and Chennai. We now deliver events in three cities together.”',
      name: 'Sana Qureshi',
      company: 'Bloom Events · Hyderabad',
      started: 'Attended a Referral Marketing Masterclass',
      action: 'Try a masterclass with the Business Guest Pass',
      pass: 'Business Guest Pass · One workshop or masterclass',
      anchor: 'pass-business',
    },
    {
      metric: '3 strategic partnerships',
      quote: '“Our team met complementary firms at a mixer. Today we co-pitch with a design studio and a cybersecurity firm we met there.”',
      name: 'Rahul Iyer',
      company: 'Stackwise IT Solutions · Bengaluru',
      started: 'Brought his team on a Business Guest Pass',
      action: 'Bring your team on a Business Guest Pass',
      pass: 'Business Guest Pass · Bring up to 3 colleagues',
      anchor: 'pass-business',
    },
  ]
  const storyQuestions = [
    ['Where do the impact numbers come from?', 'The figures reflect member activity across BNP CAFE chapters and are shared to illustrate the community’s reach.'],
    ['What counts as a referral?', 'A referral is a trusted introduction to a person or business that may benefit from a member’s products, services or expertise.'],
    ['Are the success stories from real members?', 'These stories share examples of the kinds of relationships and business outcomes members experience through networking.'],
    ['Can I share my own success story?', 'Yes. Members can share how a relationship or introduction through BNP CAFE helped their business.'],
    ['What is a guest event pass?', 'A guest pass lets you visit a BNP CAFE meeting or event and experience the community before deciding whether membership is right for you.'],
    ['Do I need to be invited to use a guest pass?', 'No. Choose the pass that fits the experience you are interested in and register to attend.'],
    ['Can I use more than one guest pass?', 'Passes are intended for different guest experiences. Check the pass details to see which option works for you.'],
  ]

  return (
    <main className="stories-page">
      <section className="stories-hero">
        <div className="stories-container stories-hero-inner">
          <div>
            <p className="eyebrow">Success stories &amp; impact</p>
            <h1>Relationships that turned into results.</h1>
            <p>Every number below started with one conversation — often at a guest visit.</p>
            <div className="stories-hero-actions">
              <Link className="button button-accent" to="/events#guest-pass">Get a guest pass <ArrowRight size={16} /></Link>
              <Link className="button button-outline" to="/membership">Join BNP CAFE <ArrowUpRight size={16} /></Link>
            </div>
          </div>
          <img alt="Entrepreneurs networking over coffee" src="/homepage-business.jpeg" />
        </div>
      </section>
      <section className="stories-metrics">
        <div className="stories-container">
          <div className="stories-section-heading">
            <p className="eyebrow">Impact metrics</p>
            <h2>The network, by the numbers.</h2>
          </div>
          <div className="stories-metric-grid">
            {metrics.map(([value, label, description]) => (
              <article className="stories-metric-card" key={label}>
                <strong>{value}</strong><h3>{label}</h3><p>{description}</p>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="stories-cases">
        <div className="stories-container">
          <div className="stories-section-heading">
            <p className="eyebrow">Member stories</p>
            <h2>From first visit to real business.</h2>
          </div>
          <div className="stories-case-grid">
            {stories.map((story) => (
              <article className="stories-case-card" key={story.name}>
                <p className="stories-case-metric">{story.metric}</p>
                <blockquote>{story.quote}</blockquote>
                <div className="stories-case-person">
                  <strong>{story.name}</strong>
                  <span>{story.company}</span>
                  <p>Started with: <Link to="/events#guest-pass">{story.started}</Link></p>
                </div>
                <div className="stories-case-footer">
                  <Link to={`/events#${story.anchor}`}>{story.action} <ArrowRight size={15} /></Link>
                  <small>{story.pass}</small>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="stories-faq">
        <div className="stories-container">
          <div className="stories-section-heading">
            <p className="eyebrow">Quick answers</p>
            <h2>Stories, numbers &amp; guest passes — FAQ.</h2>
          </div>
          <div className="stories-faq-list">
            {storyQuestions.map(([question, answer]) => (
              <details className="stories-faq-item" key={question}>
                <summary>{question}<span aria-hidden="true">+</span></summary>
                <p>{answer}</p>
              </details>
            ))}
          </div>
        </div>
      </section>
      <section className="stories-start">
        <div className="stories-container stories-start-inner">
          <div>
            <p className="eyebrow">Your story starts here</p>
            <h2>Write the next success story.</h2>
            <p>Claim a free guest pass, meet a chapter, and see what one introduction can do.</p>
          </div>
          <div className="stories-start-actions">
            <Link className="button button-accent" to="/events#guest-pass">Browse guest passes <ArrowRight size={16} /></Link>
            <Link className="button button-outline" to="/chapters">Find a chapter <ArrowRight size={16} /></Link>
          </div>
        </div>
      </section>
      <MarketingCta />
    </main>
  )
}

export function MarketingCta() {
  return (
    <section className="marketing-cta">
      <div className="marketing-cta-inner">
        <div>
          <h2>Your Next Customer Could Be One Introduction Away.</h2>
          <p>Don't just build a business. Build the network behind it.</p>
        </div>
        <div className="marketing-cta-actions">
          <Link className="button button-primary" to="/membership">Join BNP CAFE <ArrowRight size={16} /></Link>
          <Link className="button button-accent" to="/chapters">Find a Chapter <ArrowRight size={16} /></Link>
          <Link className="button button-outline" to="/events#upcoming-events">Attend an Event <ArrowRight size={16} /></Link>
        </div>
      </div>
    </section>
  )
}

const chapters = [
  { name: 'Mumbai Central', city: 'Mumbai', schedule: 'Tuesdays, 7:30 AM', members: '42 members' },
  { name: 'Bengaluru Tech Park', city: 'Bengaluru', schedule: 'Wednesdays, 7:00 AM', members: '38 members' },
  { name: 'Delhi Connaught', city: 'Delhi', schedule: 'Thursdays, 8:00 AM', members: '35 members' },
  { name: 'Pune Riverside', city: 'Pune', schedule: 'Fridays, 7:30 AM', members: '29 members' },
  { name: 'Hyderabad Hitech', city: 'Hyderabad', schedule: 'Tuesdays, 7:00 AM', members: '31 members' },
  { name: 'Chennai Marina', city: 'Chennai', schedule: 'Mondays, 7:30 AM', members: '26 members' },
]

export function ChaptersPage() {
  const [search, setSearch] = useState('')
  const filteredChapters = chapters.filter((chapter) =>
    `${chapter.name} ${chapter.city}`.toLowerCase().includes(search.trim().toLowerCase()),
  )
  const chapterBenefits = [
    'Meet local business owners',
    'Discover complementary businesses',
    'Exchange referrals',
    'Find service providers',
    'Build partnerships',
    'Share knowledge',
    'Attend local events',
    'Support fellow members',
  ]

  return (
    <main className="chapters-page">
      <section className="chapters-hero">
        <div className="chapters-container">
          <p className="eyebrow">Local Chapters</p>
          <h1>Start local. Build strong relationships. Expand your reach.</h1>
          <p>BNP CAFE chapters bring local entrepreneurs and professionals together in a structured networking environment.</p>
        </div>
      </section>
      <section className="chapter-finder">
        <div className="chapters-container">
          <div className="chapter-finder-heading">
            <div>
              <h2>Find your chapter.</h2>
              <p>Your city is full of opportunities. You just need the right network.</p>
            </div>
            <label className="chapter-search">
              <span className="sr-only">Search by city or chapter name</span>
              <Search aria-hidden="true" size={18} />
              <input
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search by city or chapter name…"
                value={search}
              />
            </label>
          </div>
          <section className="chapter-grid" aria-label="Local chapters">
            {filteredChapters.map((chapter) => (
              <article className="chapter-card" key={chapter.name}>
                <div className="chapter-card-top">
                  <span className="chapter-card-icon"><MapPin aria-hidden="true" size={20} /></span>
                  <span className="chapter-status"><span /> Open to visitors</span>
                </div>
                <h3>{chapter.name}</h3>
                <div className="chapter-details">
                  <p><MapPin aria-hidden="true" size={15} />{chapter.city}</p>
                  <p><CalendarDays aria-hidden="true" size={15} />{chapter.schedule}</p>
                  <p><Users aria-hidden="true" size={15} />{chapter.members}</p>
                </div>
                <Link className="chapter-visit-link" to="/events#guest-pass">Visit as a guest <ArrowRight size={15} /></Link>
              </article>
            ))}
            {filteredChapters.length === 0 && (
              <div className="chapter-empty">
                <h3>No chapters found.</h3>
                <p>Try searching another city or chapter name.</p>
              </div>
            )}
          </section>
        </div>
      </section>
      <section className="chapter-benefits">
        <div className="chapters-container">
          <div className="chapter-section-heading">
            <p className="eyebrow">In your chapter</p>
            <h2>Your chapter becomes your local business community.</h2>
          </div>
          <div className="chapter-benefit-grid">
            {chapterBenefits.map((benefit) => (
              <div className="chapter-benefit" key={benefit}><Check aria-hidden="true" size={17} />{benefit}</div>
            ))}
          </div>
        </div>
      </section>
      <section className="chapter-beyond">
        <div className="chapters-container">
          <p className="eyebrow">Beyond your chapter</p>
          <h2>Local relationships. Wider opportunities.</h2>
          <p>Your chapter is your starting point — not your limit. Connect with members across cities, states, industries and professional communities.</p>
        </div>
      </section>
      <MarketingCta />
    </main>
  )
}

export function PartnersPage() {
  const leaderBenefits = [
    'Build the local community',
    'Organise meetings',
    'Encourage participation',
    'Support members',
    'Create networking opportunities',
    'Strengthen referrals',
    'Promote events',
  ]
  const partnerBenefits = [
    'Corporate networking',
    'Business communities',
    'Strategic partnerships',
    'Member benefits',
    'Business events',
    'Co-branded programs',
    'Professional education',
    'Referral ecosystems',
  ]

  return (
    <main className="partners-page">
      <section className="partners-hero">
        <div className="partners-container">
          <p className="eyebrow">Leaders &amp; Partners</p>
          <h1>Let's build stronger business communities together.</h1>
          <p>Lead a chapter or bring BNP CAFE into your organisation's network.</p>
        </div>
      </section>
      <section className="partners-offer">
        <div className="partners-container partners-offer-inner">
          <div>
            <p className="eyebrow">For chapter leaders</p>
            <h2>Leadership is about helping businesses grow.</h2>
            <p>BNP CAFE chapters need leaders who understand business, relationships and community building.</p>
          </div>
          <div className="partners-offer-list">
            {leaderBenefits.map((benefit) => <span key={benefit}>{benefit}</span>)}
            <Link className="button button-primary" to="/contact">Become a Chapter Leader <ArrowRight size={16} /></Link>
          </div>
        </div>
      </section>
      <section className="partners-offer partners-offer-alt">
        <div className="partners-container partners-offer-inner">
          <div>
            <p className="eyebrow">For businesses &amp; organisations</p>
            <h2>Bring BNP CAFE into your business network.</h2>
            <p>Businesses, associations, franchise networks and professional communities can explore partnerships.</p>
          </div>
          <div className="partners-offer-list">
            {partnerBenefits.map((benefit) => <span key={benefit}>{benefit}</span>)}
            <Link className="button button-primary" to="/contact">Explore Partnerships <ArrowRight size={16} /></Link>
          </div>
        </div>
      </section>
      <MarketingCta />
    </main>
  )
}

const frequentlyAskedQuestions = [
  ['Can I connect with businesses outside my city?', 'Yes. BNP CAFE combines local chapter networking with broader digital connectivity.'],
  ['What is the Member Directory?', 'It lets eligible members discover other businesses and professionals by relevant business information.'],
  ['How does referral networking work?', "Members learn about each other's businesses and introduce trusted businesses to relevant customers, partners and contacts."],
  ['Is BNP CAFE a social media platform?', 'No. It is a professional business networking environment focused on relationships, referrals, opportunities and growth.'],
  ['Can I attend events without becoming a member?', 'Eligible events may be open to guests. Event-specific registration requirements apply.'],
  ['How do I become a member?', 'Choose a membership plan, complete the application and the applicable payment process.'],
  ['How can I start a BNP CAFE chapter?', 'Contact the BNP CAFE team to discuss chapter opportunities and eligibility.'],
  ['Can businesses partner with BNP CAFE?', 'Yes. We explore strategic partnerships with businesses, associations and organisations that share our goals.'],
]

export function FaqPage() {
  return (
    <main className="faq-page">
      <section className="faq-hero">
        <div className="faq-container">
          <p className="eyebrow">FAQs</p>
          <h1>Questions, answered.</h1>
          <p>Everything you need to know before your first BNP CAFE experience.</p>
        </div>
      </section>
      <section className="faq-questions">
        <div className="faq-container">
          <h2>Frequently asked questions</h2>
          <div className="faq-list">
            {frequentlyAskedQuestions.map(([question, answer]) => (
              <details className="faq-item" key={question}>
                <summary><span>{question}</span><span aria-hidden="true">+</span></summary>
                <p>{answer}</p>
              </details>
            ))}
          </div>
        </div>
      </section>
      <MarketingCta />
    </main>
  )
}

export function CoreTeamPage() {
  return (
    <main className="page-shell page-content public-info-page">
      <div className="page-intro">
        <p className="eyebrow">The people behind the community</p>
        <h1>Good communities are built <em>together.</em></h1>
        <p>BNP Cafe is supported by people who bring local business owners together and help each chapter make room for meaningful connections.</p>
      </div>
      <section className="public-info-hero team-info-hero">
        <span className="public-info-icon"><Users size={26} /></span>
        <div>
          <p className="eyebrow">Local leadership. Shared purpose.</p>
          <h2>Every chapter starts with people who care.</h2>
          <p>Chapter leaders and community volunteers help welcome members, host gatherings, and create the conditions for business owners to learn from one another.</p>
        </div>
      </section>
      <section className="public-info-empty team-info-empty">
        <h2>Meet the core team</h2>
        <p>Team profiles will be published here once the BNP Cafe leadership details are ready to share.</p>
        <Link className="text-link" to="/contact">Contact the BNP Cafe team <ArrowRight size={15} /></Link>
      </section>
    </main>
  )
}
