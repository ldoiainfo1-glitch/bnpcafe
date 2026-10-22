import { useState } from 'react'
import { ArrowLeft, BarChart3, CalendarDays, CircleHelp, CreditCard, Mail, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiItem } from '../lib/api'

interface RevenueTotal {
  currency: string
  amount_minor: number
}

interface ReportOverview {
  from: string
  to: string
  active_members: number
  applications_received: number
  event_registrations: number
  attendees: number
  open_support_tickets: number
  new_inquiries: number | null
  revenue_by_currency: RevenueTotal[] | null
}

function dateValue(date: Date) {
  const offset = date.getTimezoneOffset()
  return new Date(date.getTime() - offset * 60_000).toISOString().slice(0, 10)
}

export function AdminReportsPage() {
  const today = new Date()
  const monthAgo = new Date(today)
  monthAgo.setDate(monthAgo.getDate() - 30)
  const [from, setFrom] = useState(dateValue(monthAgo))
  const [to, setTo] = useState(dateValue(today))
  const [report, setReport] = useState<ReportOverview | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  async function fetchReport(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      const response = await api.get<ApiItem<ReportOverview>>('/api/admin/reports/overview', { params: { from, to } })
      setReport(response.data.data)
    } catch (requestError) {
      setReport(null)
      setError(apiErrorMessage(requestError, 'The report could not be loaded.'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="page-shell page-content admin-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to dashboard</Link>
      <div className="admin-page-heading"><div><p className="eyebrow">Platform activity</p><h1>Operational <em>reports.</em></h1><p>Choose a date range of up to 366 days. Sub Admin data is chapter-scoped.</p></div><span className="admin-queue-count"><BarChart3 size={17} /> Summary report</span></div>
      <form className="report-filter" onSubmit={fetchReport}>
        <label className="field-label">From<input max={to} onChange={(e) => setFrom(e.target.value)} required type="date" value={from} /></label>
        <label className="field-label">To<input min={from} onChange={(e) => setTo(e.target.value)} required type="date" value={to} /></label>
        <button className="button button-primary" disabled={loading} type="submit">{loading ? 'Loading…' : 'Run report'} <BarChart3 size={15} /></button>
      </form>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {!report && !loading && !error && <div className="empty-panel"><CalendarDays size={22} /><p>Select a reporting period.</p><span>Operational metrics will appear here.</span></div>}
      {report && (
        <>
          <p className="report-period">Showing activity from {report.from} through {report.to}.</p>
          <div className="account-summary-grid admin-summary-grid report-metrics">
            <ReportMetric label="Active members" value={report.active_members} icon={<Users size={18} />} />
            <ReportMetric label="Applications received" value={report.applications_received} icon={<CreditCard size={18} />} />
            <ReportMetric label="Event registrations" value={report.event_registrations} icon={<CalendarDays size={18} />} />
            <ReportMetric label="Attendees" value={report.attendees} icon={<Users size={18} />} />
            <ReportMetric label="Open support tickets" value={report.open_support_tickets} icon={<CircleHelp size={18} />} />
            {report.new_inquiries !== null && <ReportMetric label="New inquiries" value={report.new_inquiries} icon={<Mail size={18} />} />}
          </div>
          {report.revenue_by_currency !== null && (
            <section className="finance-section report-revenue">
              <div className="finance-section-heading"><span><CreditCard size={18} /></span><div><h2>Captured revenue</h2><p>Verified payment totals for the selected period.</p></div></div>
              {report.revenue_by_currency.length ? report.revenue_by_currency.map((total) => (
                <div className="finance-record" key={total.currency}><span><strong>{formatMinor(total.amount_minor, total.currency)}</strong><small>{total.currency}</small></span></div>
              )) : <p className="empty-inline">No captured revenue was recorded for this period.</p>}
            </section>
          )}
        </>
      )}
    </main>
  )
}

function ReportMetric({ label, value, icon }: { label: string; value: number; icon: React.ReactNode }) {
  return <article className="summary-card"><span>{icon}</span><div><strong>{value}</strong><small>{label}</small></div></article>
}

function formatMinor(amountMinor: number, currency: string) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amountMinor / 100)
}
