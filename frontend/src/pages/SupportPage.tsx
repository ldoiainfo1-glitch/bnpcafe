import { useEffect, useState } from 'react'
import { ArrowRight, CircleHelp, MessageSquareText, Plus, Send } from 'lucide-react'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface SupportMessage {
  id: number
  author: { id: number; name: string } | null
  message: string
  created_at: string | null
}

interface SupportTicket {
  id: number
  subject: string
  category: string
  status: string
  chapter: { id: number; name: string } | null
  messages: SupportMessage[]
  created_at: string | null
}

interface SupportPageProps {
  onNotice: (message: string) => void
}

export function SupportPage({ onNotice }: SupportPageProps) {
  const [tickets, setTickets] = useState<SupportTicket[]>([])
  const [newTicketOpen, setNewTicketOpen] = useState(false)
  const [ticket, setTicket] = useState({ subject: '', category: 'general', message: '' })
  const [reply, setReply] = useState<Record<number, string>>({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let active = true
    api.get<ApiPage<SupportTicket>>('/api/support/tickets')
      .then((response) => { if (active) setTickets(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Your support requests could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function createTicket(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      const response = await api.post<{ data: SupportTicket }>('/api/support/tickets', ticket)
      setTickets((current) => [response.data.data, ...current])
      setTicket({ subject: '', category: 'general', message: '' })
      setNewTicketOpen(false)
      onNotice('Your support request has been created.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your support request could not be created.'))
    } finally {
      setSubmitting(false)
    }
  }

  async function sendReply(ticketId: number, event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const message = reply[ticketId]?.trim()
    if (!message) return
    setError('')
    setSubmitting(true)
    try {
      const response = await api.post<{ data: SupportTicket }>(`/api/support/tickets/${ticketId}/messages`, { message })
      setTickets((current) => current.map((item) => item.id === ticketId ? response.data.data : item))
      setReply((current) => ({ ...current, [ticketId]: '' }))
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your reply could not be sent.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="page-shell page-content support-page">
      <div className="page-intro">
        <p className="eyebrow">Here when you need us</p>
        <h1>How can we <em>help?</em></h1>
        <p>Send a private message to the BNP Cafe support team and keep track of the conversation here.</p>
      </div>
      <div className="support-heading">
        <div><h2>Your support requests</h2><p>Only you and authorized BNP Cafe staff can see your requests.</p></div>
        <button className="button button-primary" onClick={() => setNewTicketOpen((open) => !open)} type="button">{newTicketOpen ? 'Close form' : 'New request'} <Plus size={16} /></button>
      </div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      {newTicketOpen && (
        <form className="form-card support-form" onSubmit={createTicket}>
          <label className="field-label">Subject<input maxLength={180} onChange={(e) => setTicket({ ...ticket, subject: e.target.value })} required value={ticket.subject} /></label>
          <label className="field-label">Topic<select onChange={(e) => setTicket({ ...ticket, category: e.target.value })} value={ticket.category}><option value="general">General</option><option value="membership">Membership</option><option value="payments">Payments</option><option value="events">Events</option><option value="account">Account</option></select></label>
          <label className="field-label">How can we help?<textarea maxLength={5000} onChange={(e) => setTicket({ ...ticket, message: e.target.value })} required rows={5} value={ticket.message} /></label>
          <button className="button button-primary" disabled={submitting} type="submit">{submitting ? 'Sending…' : 'Create support request'} <ArrowRight size={15} /></button>
        </form>
      )}
      {loading ? <p className="loading-copy">Loading your support requests…</p> : tickets.length ? (
        <div className="support-list">
          {tickets.map((item) => (
            <details className="support-ticket" key={item.id}>
              <summary>
                <span className="support-ticket-icon"><CircleHelp size={17} /></span>
                <span className="support-ticket-title"><strong>{item.subject}</strong><small>{item.category} · {item.chapter?.name ?? 'General support'} · {item.created_at ? formatDate(item.created_at) : 'Recently'}</small></span>
                <span className={`status-pill status-${item.status}`}>{humanize(item.status)}</span>
              </summary>
              <div className="support-thread">
                {item.messages.map((message) => (
                  <article className="thread-message" key={message.id}>
                    <div><strong>{message.author?.name ?? 'BNP Cafe team'}</strong><small>{message.created_at ? formatDate(message.created_at) : ''}</small></div>
                    <p>{message.message}</p>
                  </article>
                ))}
                {!['closed', 'resolved'].includes(item.status) && (
                  <form className="reply-form" onSubmit={(event) => void sendReply(item.id, event)}>
                    <label className="field-label">Reply<textarea onChange={(e) => setReply({ ...reply, [item.id]: e.target.value })} required rows={3} value={reply[item.id] ?? ''} /></label>
                    <button aria-label="Send reply" className="button button-small button-primary" disabled={submitting} type="submit"><Send size={14} /> Send reply</button>
                  </form>
                )}
              </div>
            </details>
          ))}
        </div>
      ) : (
        <div className="empty-panel"><MessageSquareText size={24} /><p>You don’t have any support requests.</p><span>When you need help, start a private conversation with our support team.</span></div>
      )}
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
