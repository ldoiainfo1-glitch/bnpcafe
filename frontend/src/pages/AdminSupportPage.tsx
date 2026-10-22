import { useEffect, useState } from 'react'
import { ArrowLeft, CircleHelp, Send, ShieldAlert } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface AdminMessage {
  id: number
  author: { id: number; name: string } | null
  message: string
  created_at: string | null
  is_internal?: boolean
}

interface AdminTicket {
  id: number
  requester: { id: number; name: string; email: string }
  chapter: { id: number; name: string } | null
  subject: string
  category: string
  priority: string
  status: string
  messages: AdminMessage[]
}

interface AdminSupportPageProps {
  onNotice: (message: string) => void
}

export function AdminSupportPage({ onNotice }: AdminSupportPageProps) {
  const [tickets, setTickets] = useState<AdminTicket[]>([])
  const [drafts, setDrafts] = useState<Record<number, string>>({})
  const [internalNotes, setInternalNotes] = useState<Record<number, boolean>>({})
  const [loading, setLoading] = useState(true)
  const [savingId, setSavingId] = useState<number | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<AdminTicket>>('/api/admin/support/tickets')
      .then((response) => { if (active) setTickets(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'The support queue could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function updateTicket(ticket: AdminTicket, values: { status?: string; priority?: string }) {
    setError('')
    setSavingId(ticket.id)
    try {
      const response = await api.patch<{ data: AdminTicket }>(`/api/admin/support/tickets/${ticket.id}`, values)
      setTickets((current) => current.map((item) => item.id === ticket.id ? response.data.data : item))
      onNotice('Support request updated.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'The support request could not be updated.'))
    } finally {
      setSavingId(null)
    }
  }

  async function reply(ticket: AdminTicket, event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const message = drafts[ticket.id]?.trim()
    if (!message) return
    setError('')
    setSavingId(ticket.id)
    try {
      const response = await api.post<{ data: AdminTicket }>(`/api/support/tickets/${ticket.id}/messages`, {
        message,
        is_internal: internalNotes[ticket.id] ?? false,
      })
      setTickets((current) => current.map((item) => item.id === ticket.id ? response.data.data : item))
      setDrafts((current) => ({ ...current, [ticket.id]: '' }))
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your reply could not be sent.'))
    } finally {
      setSavingId(null)
    }
  }

  return (
    <main className="page-shell page-content admin-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to dashboard</Link>
      <div className="admin-page-heading"><div><p className="eyebrow">Private member support</p><h1>Support <em>queue.</em></h1><p>Respond to members and keep chapter support moving.</p></div><span className="admin-queue-count"><CircleHelp size={17} /> {tickets.filter((ticket) => !['resolved', 'closed'].includes(ticket.status)).length} open requests</span></div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {loading ? <p className="loading-copy">Loading the support queue…</p> : tickets.length ? (
        <div className="admin-support-list">
          {tickets.map((ticket) => {
            const closed = ['resolved', 'closed'].includes(ticket.status)
            return (
              <details className="admin-ticket" key={ticket.id}>
                <summary><span className="support-ticket-icon"><CircleHelp size={17} /></span><span className="support-ticket-title"><strong>{ticket.subject}</strong><small>{ticket.requester.name} · {ticket.chapter?.name ?? 'General support'} · {ticket.category}</small></span><span className={`status-pill status-${ticket.priority}`}>{ticket.priority}</span><span className={`status-pill status-${ticket.status}`}>{humanize(ticket.status)}</span></summary>
                <div className="admin-ticket-body">
                  <div className="admin-ticket-toolbar">
                    <label className="field-label">Status<select disabled={closed || savingId === ticket.id} onChange={(e) => void updateTicket(ticket, { status: e.target.value })} value={ticket.status}>{ticket.status === 'open' && <option disabled value="open">Open</option>}<option value="in_progress">In progress</option><option value="escalated">Escalated</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></label>
                    <label className="field-label">Priority<select disabled={closed || savingId === ticket.id} onChange={(e) => void updateTicket(ticket, { priority: e.target.value })} value={ticket.priority}><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></label>
                  </div>
                  <p className="admin-requester-email">{ticket.requester.email}</p>
                  <div className="support-thread">
                    {ticket.messages.map((message) => (
                      <article className={`thread-message${message.is_internal ? ' thread-message-internal' : ''}`} key={message.id}>
                        <div><strong>{message.author?.name ?? 'BNP Cafe team'}{message.is_internal ? ' · Internal note' : ''}</strong><small>{message.created_at ? formatDate(message.created_at) : ''}</small></div>
                        <p>{message.message}</p>
                      </article>
                    ))}
                  </div>
                  {!closed && (
                    <form className="reply-form admin-reply-form" onSubmit={(event) => void reply(ticket, event)}>
                      <label className="field-label">Reply<textarea onChange={(e) => setDrafts({ ...drafts, [ticket.id]: e.target.value })} required rows={3} value={drafts[ticket.id] ?? ''} /></label>
                      <label className="checkbox-row"><input checked={internalNotes[ticket.id] ?? false} onChange={(e) => setInternalNotes({ ...internalNotes, [ticket.id]: e.target.checked })} type="checkbox" /><span><ShieldAlert size={14} /> Add as staff-only internal note</span></label>
                      <button className="button button-small button-primary" disabled={savingId === ticket.id} type="submit"><Send size={14} /> Send reply</button>
                    </form>
                  )}
                </div>
              </details>
            )
          })}
        </div>
      ) : <div className="empty-panel"><CircleHelp size={22} /><p>The support queue is clear.</p><span>New member requests will appear here.</span></div>}
    </main>
  )
}

function formatDate(value: string) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
}

function humanize(value: string) {
  return value.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}
