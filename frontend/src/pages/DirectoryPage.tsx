import { useEffect, useState } from 'react'
import { ArrowRight, BriefcaseBusiness, Globe2, Search, Users } from 'lucide-react'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface DirectoryMember {
  id: number
  name: string
  chapter: { id: number; name: string }
  business_name: string | null
  industry: string | null
  designation: string | null
  bio: string | null
  website: string | null
  email: string | null
  phone: string | null
}

export function DirectoryPage() {
  const [members, setMembers] = useState<DirectoryMember[]>([])
  const [search, setSearch] = useState('')
  const [industry, setIndustry] = useState('')
  const [submittedSearch, setSubmittedSearch] = useState('')
  const [submittedIndustry, setSubmittedIndustry] = useState('')
  const [refreshIndex, setRefreshIndex] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<DirectoryMember>>('/api/directory', {
      params: {
        ...(submittedSearch ? { search: submittedSearch } : {}),
        ...(submittedIndustry ? { industry: submittedIndustry } : {}),
      },
    })
      .then((response) => { if (active) setMembers(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'The member directory could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [submittedSearch, submittedIndustry, refreshIndex])

  function searchDirectory(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setLoading(true)
    setSubmittedSearch(search.trim())
    setSubmittedIndustry(industry.trim())
    setRefreshIndex((current) => current + 1)
  }

  return (
    <main className="page-shell page-content directory-page">
      <div className="page-intro">
        <p className="eyebrow">Business Directory</p>
        <h1>Discover businesses. Find expertise. Create connections.</h1>
        <p>Stop searching randomly. Start connecting intentionally.</p>
      </div>
      <form className="directory-search" onSubmit={searchDirectory}>
        <label className="field-label">Search members or businesses…<input onChange={(e) => setSearch(e.target.value)} placeholder="Search members or businesses…" value={search} /></label>
        <label className="field-label">Industry<input onChange={(e) => setIndustry(e.target.value)} placeholder="Filter by industry" value={industry} /></label>
        <button aria-label="Search directory" className="button button-primary" type="submit"><Search size={16} /> Search</button>
      </form>
      <div className="directory-heading"><p>{loading ? 'Finding members…' : `${members.length} ${members.length === 1 ? 'member' : 'members'} shown`}</p><span><Users size={15} /> Opted-in members only</span></div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {loading ? <p className="loading-copy">Loading the member directory…</p> : members.length ? (
        <div className="directory-grid">
          {members.map((member) => (
            <article className="directory-card" key={member.id}>
              <div className="directory-avatar">{member.name.trim().charAt(0).toUpperCase()}</div>
              <span className="directory-chapter">{member.chapter.name}</span>
              <h2>{member.name}</h2>
              {member.designation && <p className="directory-designation">{member.designation}</p>}
              {member.business_name && <p className="directory-business"><BriefcaseBusiness size={14} />{member.business_name}</p>}
              {member.industry && <span className="industry-tag">{member.industry}</span>}
              {member.bio && <p className="directory-bio">{member.bio}</p>}
              <div className="directory-links">
                {member.email && <a href={`mailto:${member.email}`}>{member.email}<ArrowRight size={13} /></a>}
                {member.phone && <a href={`tel:${member.phone}`}>{member.phone}<ArrowRight size={13} /></a>}
                {member.website && <a href={safeWebsiteUrl(member.website)} rel="noreferrer" target="_blank">Visit website <Globe2 size={13} /></a>}
              </div>
            </article>
          ))}
        </div>
      ) : (
        <div className="empty-panel"><Users size={24} /><p>No directory members match your search.</p><span>Try a different keyword or check back as more members choose to be listed.</span></div>
      )}
    </main>
  )
}

function safeWebsiteUrl(value: string) {
  try {
    const url = new URL(value)
    return ['http:', 'https:'].includes(url.protocol) ? url.toString() : '#'
  } catch {
    return '#'
  }
}
