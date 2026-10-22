import { useEffect, useState } from 'react'
import { ArrowLeft, Check, ClipboardList, RotateCw, X } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface MembershipApplication {
  id: number
  applicant: { id: number; name: string; email: string }
  chapter: { id: number; name: string }
  plan: { id: number; name: string; price: string; currency: string; duration_months: number }
  phone: string
  business_name: string
  industry: string
  designation: string | null
  business_address: string | null
  applicant_note: string | null
  status: string
  review_note: string | null
  membership: { id: number; status: string; starts_at: string | null; ends_at: string | null } | null
  created_at: string | null
}

interface AdminApplicationsPageProps {
  onNotice: (message: string) => void
}

export function AdminApplicationsPage({ onNotice }: AdminApplicationsPageProps) {
  const [applications, setApplications] = useState<MembershipApplication[]>([])
  const [reviewNotes, setReviewNotes] = useState<Record<number, string>>({})
  const [loading, setLoading] = useState(true)
  const [savingId, setSavingId] = useState<number | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<MembershipApplication>>('/api/admin/membership-applications')
      .then((response) => { if (active) setApplications(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'The application queue could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function review(application: MembershipApplication, status: string) {
    setError('')
    setSavingId(application.id)
    try {
      const response = await api.patch<{ data: MembershipApplication }>(`/api/admin/membership-applications/${application.id}`, {
        status,
        review_note: reviewNotes[application.id] || null,
      })
      setApplications((current) => current.map((item) => item.id === application.id ? response.data.data : item))
      onNotice(status === 'approved' ? 'Application approved. The applicant can continue to payment.' : `Application ${status.replace('_', ' ')}.`)
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'The application could not be updated.'))
    } finally {
      setSavingId(null)
    }
  }

  const openApplications = applications.filter((application) => ['submitted', 'under_review'].includes(application.status))

  return (
    <main className="page-shell page-content admin-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to dashboard</Link>
      <div className="admin-page-heading">
        <div><p className="eyebrow">Chapter operations</p><h1>Membership <em>applications.</em></h1><p>Review applications for chapters you’re authorized to manage.</p></div>
        <span className="admin-queue-count"><ClipboardList size={17} /> {openApplications.length} awaiting review</span>
      </div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {loading ? <p className="loading-copy">Loading the application queue…</p> : error ? null : applications.length ? (
        <div className="admin-application-list">
          {applications.map((application) => (
            <article className="admin-application" key={application.id}>
              <div className="admin-application-heading">
                <div><span className="admin-application-id">APPLICATION #{application.id}</span><h2>{application.applicant.name}</h2><p>{application.applicant.email} · {application.created_at ? formatDate(application.created_at) : 'Recently submitted'}</p></div>
                <span className={`status-pill status-${application.status}`}>{humanize(application.status)}</span>
              </div>
              <div className="admin-application-details">
                <div><small>Requested plan</small><strong>{application.plan.name}</strong><span>{formatPrice(application.plan.price, application.plan.currency)} / {application.plan.duration_months} months</span></div>
                <div><small>Chapter</small><strong>{application.chapter.name}</strong><span>{application.industry}</span></div>
                <div><small>Business</small><strong>{application.business_name}</strong><span>{application.designation || 'Role not provided'}</span></div>
              </div>
              <details className="application-more"><summary>Application details</summary><div className="application-more-content"><p><strong>Phone:</strong> {application.phone}</p>{application.business_address && <p><strong>Address:</strong> {application.business_address}</p>}{application.applicant_note && <p><strong>Applicant note:</strong> {application.applicant_note}</p>}{application.review_note && <p><strong>Previous review note:</strong> {application.review_note}</p>}{application.membership && <p><strong>Membership status:</strong> {humanize(application.membership.status)}</p>}</div></details>
              {['submitted', 'under_review'].includes(application.status) ? (
                <div className="application-review-controls">
                  <label className="field-label">Review note <span className="field-optional">Optional</span><textarea maxLength={2000} onChange={(e) => setReviewNotes({ ...reviewNotes, [application.id]: e.target.value })} rows={2} value={reviewNotes[application.id] ?? ''} /></label>
                  <div className="review-actions">
                    {application.status === 'submitted' && <button className="button button-outline-dark" disabled={savingId === application.id} onClick={() => void review(application, 'under_review')} type="button"><RotateCw size={14} /> Start review</button>}
                    <button className="button button-primary" disabled={savingId === application.id} onClick={() => void review(application, 'approved')} type="button"><Check size={15} /> Approve</button>
                    <button className="button button-danger" disabled={savingId === application.id} onClick={() => void review(application, 'rejected')} type="button"><X size={15} /> Reject</button>
                  </div>
                </div>
              ) : (
                <p className="review-complete-note">{application.status === 'approved' ? 'Approved; next step depends on the membership plan.' : 'This application has been reviewed.'}</p>
              )}
            </article>
          ))}
        </div>
      ) : (
        <div className="empty-panel"><ClipboardList size={24} /><p>No membership applications to review.</p><span>New applications from your assigned chapters will appear here.</span></div>
      )}
    </main>
  )
}

function formatDate(value: string) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Recently submitted' : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
}

function formatPrice(price: string, currency: string) {
  const amount = Number(price)
  return Number.isFinite(amount) ? new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount) : `${currency} ${price}`
}

function humanize(value: string) {
  return value.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}
