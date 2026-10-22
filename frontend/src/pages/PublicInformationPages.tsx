import { useEffect, useState } from 'react'
import { ArrowRight, BookOpenText, Building2, Coffee, HeartHandshake, Lightbulb, MessageSquareQuote, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage, ContentSummary } from '../lib/api'

export function AboutPage() {
  return (
    <main className="page-shell page-content public-info-page">
      <div className="page-intro">
        <p className="eyebrow">About BNP Cafe</p>
        <h1>Business grows <em>better together.</em></h1>
        <p>BNP Cafe brings local business owners together to build trusted relationships, share experience, and find new opportunities to grow.</p>
      </div>
      <section className="public-info-hero about-info-hero">
        <span className="public-info-icon"><Coffee size={26} /></span>
        <div>
          <p className="eyebrow">A community built on connection</p>
          <h2>More than a meeting. A network that shows up.</h2>
          <p>We make it easier for entrepreneurs to meet people in their local business community. Through chapter gatherings, events, and member connections, BNP Cafe creates space for useful conversations and lasting professional relationships.</p>
        </div>
      </section>
      <section className="public-info-values" aria-label="What BNP Cafe is about">
        <article className="public-info-card">
          <span className="public-info-icon"><HeartHandshake size={22} /></span>
          <h2>People first</h2>
          <p>Build genuine relationships with local business owners who value trust, collaboration, and mutual support.</p>
        </article>
        <article className="public-info-card">
          <span className="public-info-icon"><Lightbulb size={22} /></span>
          <h2>Learn together</h2>
          <p>Share practical knowledge and experience so every conversation can lead to a useful new idea.</p>
        </article>
        <article className="public-info-card">
          <span className="public-info-icon"><Building2 size={22} /></span>
          <h2>Grow locally</h2>
          <p>Connect with your local chapter, discover events, and create opportunities within your community.</p>
        </article>
      </section>
      <section className="public-info-cta">
        <div>
          <p className="eyebrow">Your next introduction starts here</p>
          <h2>Find your business community.</h2>
        </div>
        <div className="public-info-cta-actions">
          <Link className="button button-primary" to="/membership">Explore membership <ArrowRight size={16} /></Link>
          <Link className="text-link" to="/events">See upcoming events <ArrowRight size={15} /></Link>
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
  return (
    <main className="page-shell page-content public-info-page">
      <div className="page-intro">
        <p className="eyebrow">Member voices</p>
        <h1>Good connections make <em>a difference.</em></h1>
        <p>BNP Cafe is about the people who share their time, ideas, and experience with the business community.</p>
      </div>
      <section className="public-info-empty">
        <span className="public-info-icon"><MessageSquareQuote size={24} /></span>
        <h2>Member stories are coming soon.</h2>
        <p>We’re gathering stories directly from BNP Cafe members. We’ll share their experiences here once they’re ready.</p>
        <Link className="button button-primary" to="/membership">Become part of the community <ArrowRight size={16} /></Link>
      </section>
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
