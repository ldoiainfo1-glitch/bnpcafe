import { useEffect, useState } from 'react'
import { ArrowLeft, ArrowRight, CheckCircle2 } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiItem, ApiPage, ChapterSummary, MemberProfile, MembershipPlan } from '../lib/api'

interface MembershipApplyPageProps {
  onNotice: (message: string) => void
}

interface ApplicationForm {
  chapter_id: string
  membership_plan_id: string
  phone: string
  business_name: string
  industry: string
  designation: string
  business_address: string
  applicant_note: string
}

const emptyForm: ApplicationForm = {
  chapter_id: '',
  membership_plan_id: '',
  phone: '',
  business_name: '',
  industry: '',
  designation: '',
  business_address: '',
  applicant_note: '',
}

export function MembershipApplyPage({ onNotice }: MembershipApplyPageProps) {
  const navigate = useNavigate()
  const [chapters, setChapters] = useState<ChapterSummary[]>([])
  const [plans, setPlans] = useState<MembershipPlan[]>([])
  const [form, setForm] = useState<ApplicationForm>(emptyForm)
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    Promise.all([
      api.get<ApiPage<ChapterSummary>>('/api/chapters', { params: { per_page: 100 } }),
      api.get<ApiPage<MembershipPlan>>('/api/membership-plans', { params: { per_page: 100 } }),
      api.get<ApiItem<MemberProfile | null>>('/api/profile'),
    ])
      .then(([chapterResponse, planResponse, profileResponse]) => {
        if (!active) return
        const availablePlans = planResponse.data.data
        const profile = profileResponse.data.data
        setChapters(chapterResponse.data.data)
        setPlans(availablePlans)
        setForm({
          ...emptyForm,
          membership_plan_id: availablePlans[0] ? String(availablePlans[0].id) : '',
          phone: profile?.phone ?? '',
          business_name: profile?.business_name ?? '',
          industry: profile?.industry ?? '',
          designation: profile?.designation ?? '',
          business_address: profile?.business_address ?? '',
        })
      })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'We could not load the application options.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await api.post('/api/membership-applications', {
        ...form,
        chapter_id: Number(form.chapter_id),
        membership_plan_id: Number(form.membership_plan_id),
      })
      onNotice('Your application has been submitted for review.')
      navigate('/account')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your application could not be submitted.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="page-shell page-content application-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to your dashboard</Link>
      <div className="page-intro">
        <p className="eyebrow">Your next chapter starts here</p>
        <h1>Apply for <em>membership.</em></h1>
        <p>Tell us a little about your business and choose the chapter and membership that are right for you.</p>
      </div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {loading ? <p className="loading-copy">Preparing your application…</p> : chapters.length === 0 || plans.length === 0 ? (
        <div className="empty-panel"><CheckCircle2 size={24} /><p>Applications are not available right now.</p><span>Please contact the BNP Cafe team and we’ll help you with the next step.</span><Link className="text-link" to="/contact">Contact us <ArrowRight size={15} /></Link></div>
      ) : (
        <form className="form-card application-form" onSubmit={submit}>
          <div className="form-heading"><span className="form-step">01</span><div><h2>Your membership</h2><p>Applications are reviewed by the chapter team.</p></div></div>
          <div className="form-row">
            <label className="field-label">Choose a chapter<select onChange={(e) => setForm({ ...form, chapter_id: e.target.value })} required value={form.chapter_id}><option value="">Select a chapter</option>{chapters.map((chapter) => <option key={chapter.id} value={chapter.id}>{chapter.name}{chapter.state ? ` · ${chapter.state}` : ''}</option>)}</select></label>
            <label className="field-label">Membership plan<select onChange={(e) => setForm({ ...form, membership_plan_id: e.target.value })} required value={form.membership_plan_id}><option value="">Select a plan</option>{plans.map((plan) => <option key={plan.id} value={plan.id}>{plan.name} — {formatPrice(plan.price, plan.currency)} / {plan.duration_months} months</option>)}</select></label>
          </div>
          <div className="form-heading application-section-heading"><span className="form-step">02</span><div><h2>About your business</h2><p>Your profile is kept private unless you choose directory visibility.</p></div></div>
          <div className="form-row">
            <label className="field-label">Phone<input autoComplete="tel" onChange={(e) => setForm({ ...form, phone: e.target.value })} required value={form.phone} /></label>
            <label className="field-label">Business name<input autoComplete="organization" onChange={(e) => setForm({ ...form, business_name: e.target.value })} required value={form.business_name} /></label>
          </div>
          <div className="form-row">
            <label className="field-label">Industry<input onChange={(e) => setForm({ ...form, industry: e.target.value })} required value={form.industry} /></label>
            <label className="field-label">Your role <span className="field-optional">Optional</span><input onChange={(e) => setForm({ ...form, designation: e.target.value })} value={form.designation} /></label>
          </div>
          <label className="field-label">Business address <span className="field-optional">Optional</span><textarea onChange={(e) => setForm({ ...form, business_address: e.target.value })} rows={3} value={form.business_address} /></label>
          <label className="field-label">Anything you’d like us to know? <span className="field-optional">Optional</span><textarea maxLength={2000} onChange={(e) => setForm({ ...form, applicant_note: e.target.value })} rows={4} value={form.applicant_note} /></label>
          <p className="privacy-callout"><CheckCircle2 size={15} /> Your application is shared only with authorized reviewers for your selected chapter.</p>
          <button className="button button-primary" disabled={submitting} type="submit">{submitting ? 'Submitting…' : 'Submit application'} <ArrowRight size={16} /></button>
        </form>
      )}
    </main>
  )
}

function formatPrice(price: string, currency: string) {
  const amount = Number(price)
  return Number.isFinite(amount) ? new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount) : `${currency} ${price}`
}
