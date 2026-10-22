import { useEffect, useState } from 'react'
import { ArrowLeft, FileText, Plus, Save } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface EditablePage {
  id: number
  slug: string
  title: string
  summary: string | null
  body: string
  status: 'draft' | 'published'
  updated_at?: string | null
}

interface PageForm {
  slug: string
  title: string
  summary: string
  body: string
  status: 'draft' | 'published'
}

interface AdminContentPageProps {
  onNotice: (message: string) => void
}

const emptyForm: PageForm = { slug: '', title: '', summary: '', body: '', status: 'draft' }

export function AdminContentPage({ onNotice }: AdminContentPageProps) {
  const [pages, setPages] = useState<EditablePage[]>([])
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [form, setForm] = useState<PageForm>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<EditablePage>>('/api/admin/content/pages')
      .then((response) => { if (active) setPages(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Public content pages could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  function editPage(page: EditablePage) {
    setSelectedId(page.id)
    setForm({
      slug: page.slug,
      title: page.title,
      summary: page.summary ?? '',
      body: page.body,
      status: page.status,
    })
    setError('')
  }

  async function savePage(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSaving(true)
    try {
      const response = selectedId
        ? await api.patch<{ data: EditablePage }>(`/api/admin/content/pages/${selectedId}`, form)
        : await api.post<{ data: EditablePage }>('/api/admin/content/pages', form)
      setPages((current) => selectedId
        ? current.map((page) => page.id === selectedId ? response.data.data : page)
        : [response.data.data, ...current])
      setSelectedId(response.data.data.id)
      setForm({
        slug: response.data.data.slug,
        title: response.data.data.title,
        summary: response.data.data.summary ?? '',
        body: response.data.data.body,
        status: response.data.data.status,
      })
      onNotice(selectedId ? 'Content page updated.' : 'Content page created.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'This content page could not be saved.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <main className="page-shell page-content admin-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to dashboard</Link>
      <div className="admin-page-heading"><div><p className="eyebrow">Public website</p><h1>Content <em>pages.</em></h1><p>Page text is rendered as plain text on the public site.</p></div><button className="button button-primary" onClick={() => { setSelectedId(null); setForm(emptyForm); setError('') }} type="button"><Plus size={15} /> New page</button></div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      <div className="content-admin-layout">
        <section className="content-admin-list">
          <h2><FileText size={16} /> Pages</h2>
          {loading ? <p className="loading-copy">Loading content pages…</p> : pages.length ? pages.map((page) => (
            <button className={`content-admin-item${selectedId === page.id ? ' selected' : ''}`} key={page.id} onClick={() => editPage(page)} type="button"><span><strong>{page.title}</strong><small>/{page.slug}</small></span><span className={`status-pill status-${page.status}`}>{page.status}</span></button>
          )) : <p className="empty-inline">No pages created.</p>}
        </section>
        <form className="form-card content-admin-form" onSubmit={savePage}>
          <div className="form-heading"><span className="form-step"><FileText size={16} /></span><div><h2>{selectedId ? 'Edit page' : 'Create a page'}</h2><p>Only published pages are visible to the public.</p></div></div>
          <label className="field-label">Page title<input maxLength={180} onChange={(e) => setForm({ ...form, title: e.target.value })} required value={form.title} /></label>
          <label className="field-label">URL slug<input maxLength={100} onChange={(e) => setForm({ ...form, slug: e.target.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-|-$/g, '') })} pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="about-our-community" required value={form.slug} /></label>
          <label className="field-label">Short summary <span className="field-optional">Optional</span><textarea maxLength={500} onChange={(e) => setForm({ ...form, summary: e.target.value })} rows={2} value={form.summary} /></label>
          <label className="field-label">Page body<textarea maxLength={50000} onChange={(e) => setForm({ ...form, body: e.target.value })} required rows={12} value={form.body} /></label>
          <label className="field-label">Publication status<select onChange={(e) => setForm({ ...form, status: e.target.value as PageForm['status'] })} value={form.status}><option value="draft">Draft</option><option value="published">Published</option></select></label>
          <button className="button button-primary" disabled={saving} type="submit"><Save size={15} />{saving ? 'Saving…' : selectedId ? 'Save changes' : 'Create page'}</button>
        </form>
      </div>
    </main>
  )
}
