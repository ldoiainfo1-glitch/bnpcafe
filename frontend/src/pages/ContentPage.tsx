import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiItem, ContentSummary } from '../lib/api'

export function ContentPage() {
  const { slug = '' } = useParams()
  const [page, setPage] = useState<ContentSummary | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true
    api.get<ApiItem<ContentSummary>>(`/api/content/pages/${encodeURIComponent(slug)}`)
      .then((response) => { if (active) setPage(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'This page could not be found.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [slug])

  if (loading) return <main className="page-shell page-content"><p className="loading-copy">Loading page…</p></main>
  if (!page) return <main className="page-shell page-content"><div className="empty-panel"><p>{error || 'This page could not be found.'}</p></div></main>

  return (
    <main className="page-shell page-content content-page">
      <p className="eyebrow">BNP Cafe community</p>
      <h1>{page.title}</h1>
      {page.summary && <p className="content-summary">{page.summary}</p>}
      <div className="content-body">{page.body}</div>
    </main>
  )
}
