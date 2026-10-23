import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Search } from 'lucide-react'

const categories = ['All', 'Finance', 'Design', 'Legal', 'Technology', 'Events', 'Logistics']

const sampleListings = [
  { initials: 'PS', business: 'Ledgerline Accounting', member: 'Priya Sharma', city: 'Mumbai', category: 'Finance' },
  { initials: 'AM', business: 'Brightside Interiors', member: 'Arjun Mehta', city: 'Pune', category: 'Design' },
  { initials: 'NK', business: 'Kapoor Legal Associates', member: 'Neha Kapoor', city: 'Delhi', category: 'Legal' },
  { initials: 'RI', business: 'Stackwise IT Solutions', member: 'Rahul Iyer', city: 'Bengaluru', category: 'Technology' },
  { initials: 'SQ', business: 'Bloom Events', member: 'Sana Qureshi', city: 'Hyderabad', category: 'Events' },
  { initials: 'VR', business: 'Rao Logistics', member: 'Vikram Rao', city: 'Chennai', category: 'Logistics' },
]

const referralPrompts = [
  'Who do you serve?',
  'What problem do you solve?',
  'What makes you different?',
  'Who is your ideal customer?',
  'Who are your best referral partners?',
  'What introduction would help most?',
]

export function PublicDirectoryPage() {
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('All')
  const visibleListings = useMemo(() => {
    const query = search.trim().toLocaleLowerCase()
    return sampleListings.filter((listing) => {
      const matchesCategory = category === 'All' || listing.category === category
      const matchesQuery = !query || Object.values(listing).some((value) => value.toLocaleLowerCase().includes(query))
      return matchesCategory && matchesQuery
    })
  }, [category, search])

  return (
    <main className="business-directory-page">
      <section className="business-directory-hero">
        <div className="business-directory-container">
          <p className="eyebrow">Business Directory</p>
          <h1>Discover businesses. Find expertise. Create connections.</h1>
          <p>Stop searching randomly. Start connecting intentionally.</p>
        </div>
      </section>

      <section aria-labelledby="business-directory-search-title" className="business-directory-search-section">
        <div className="business-directory-container">
          <div className="business-directory-section-heading">
            <h2 id="business-directory-search-title">Search the network</h2>
            <p>Search by business name, member name, category, city, industry or services. Sample listings shown.</p>
          </div>
          <label className="business-directory-search">
            <Search aria-hidden="true" size={18} />
            <span className="sr-only">Search members or businesses</span>
            <input
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search members or businesses…"
              type="search"
              value={search}
            />
          </label>
          <div aria-label="Filter directory by category" className="business-directory-filters" role="group">
            {categories.map((item) => (
              <button
                aria-pressed={category === item}
                className="business-directory-filter"
                key={item}
                onClick={() => setCategory(item)}
                type="button"
              >
                {item}
              </button>
            ))}
          </div>
          {visibleListings.length ? (
            <div aria-live="polite" className="business-directory-grid">
              {visibleListings.map((listing) => (
                <article className="business-directory-card" key={listing.business}>
                  <span aria-hidden="true" className="business-directory-avatar">{listing.initials}</span>
                  <div>
                    <h3>{listing.business}</h3>
                    <p>{listing.member} · {listing.city}</p>
                    <span className="business-directory-category">{listing.category}</span>
                  </div>
                </article>
              ))}
            </div>
          ) : (
            <p className="business-directory-empty" role="status">No sample listings match your search. Try another business, category, or city.</p>
          )}
        </div>
      </section>

      <section aria-labelledby="business-directory-referrals-title" className="business-directory-referrals">
        <div className="business-directory-container">
          <p className="eyebrow">Referral Network</p>
          <h2 id="business-directory-referrals-title">Give better referrals. Receive better referrals.</h2>
          <p>A referral is more powerful when there is trust behind it. A better referral starts with knowing:</p>
          <ul>
            {referralPrompts.map((prompt) => <li key={prompt}>{prompt}</li>)}
          </ul>
        </div>
      </section>

      <section className="marketing-cta">
        <div className="marketing-cta-inner">
          <div>
            <h2>Your Next Customer Could Be One Introduction Away.</h2>
            <p>Don&apos;t just build a business. Build the network behind it.</p>
          </div>
          <div className="marketing-cta-actions">
            <Link className="button button-accent" to="/membership">Join BNP CAFE <ArrowRight size={15} /></Link>
            <Link className="button button-outline" to="/chapters">Find a Chapter <ArrowRight size={15} /></Link>
            <Link className="button button-outline" to="/events#upcoming-events">Attend an Event <ArrowRight size={15} /></Link>
          </div>
        </div>
      </section>
    </main>
  )
}
