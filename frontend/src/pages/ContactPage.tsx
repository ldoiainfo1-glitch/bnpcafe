import { useState } from 'react'
import { ArrowUpRight, Mail, MapPin, MessageCircle } from 'lucide-react'
import { api, apiErrorMessage, prepareSessionRequest } from '../lib/api'
import type { ContactInquiryInput } from '../lib/api'

interface ContactPageProps {
  onNotice: (message: string) => void
}

export function ContactPage({ onNotice }: ContactPageProps) {
  const [form, setForm] = useState<ContactInquiryInput>({ name: '', email: '', phone: '', subject: '', message: '' })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await prepareSessionRequest()
      await api.post('/api/contact-inquiries', form)
      onNotice('Thanks for reaching out. Our team will be in touch.')
      setForm({ name: '', email: '', phone: '', subject: '', message: '' })
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your message could not be sent. Please try again.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="page-shell page-content contact-page">
      <div className="page-intro">
        <p className="eyebrow">Let’s start a conversation</p>
        <h1>We’d love to <em>hear from you.</em></h1>
        <p>Curious about joining, hosting an event, or what BNP Cafe is all about? Send us a note.</p>
      </div>
      <div className="contact-grid">
        <aside className="contact-aside">
          <h2>We’re here to help.</h2>
          <p>Tell us a little about what you’re looking for and someone from the community will get back to you.</p>
          <div className="contact-detail"><span><Mail size={18} /></span><div><strong>Email</strong><small>Use the message form and we’ll reply to your inbox.</small></div></div>
          <div className="contact-detail"><span><MapPin size={18} /></span><div><strong>Find your chapter</strong><small>Meet local members and business owners in your area.</small></div></div>
          <div className="contact-detail"><span><MessageCircle size={18} /></span><div><strong>Good questions welcome</strong><small>We’re happy to help you find your next step.</small></div></div>
        </aside>
        <form className="form-card contact-form" onSubmit={submit}>
          <div className="form-heading"><span className="form-step">01</span><div><h2>Send us a message</h2><p>We’ll be in touch as soon as we can.</p></div></div>
          {error && <p className="inline-error" role="alert">{error}</p>}
          <div className="form-row">
            <label className="field-label">Your name<input autoComplete="name" onChange={(e) => setForm({ ...form, name: e.target.value })} required value={form.name} /></label>
            <label className="field-label">Email address<input autoComplete="email" onChange={(e) => setForm({ ...form, email: e.target.value })} required type="email" value={form.email} /></label>
          </div>
          <div className="form-row">
            <label className="field-label">Phone <span className="field-optional">Optional</span><input autoComplete="tel" onChange={(e) => setForm({ ...form, phone: e.target.value })} value={form.phone} /></label>
            <label className="field-label">Subject <span className="field-optional">Optional</span><input onChange={(e) => setForm({ ...form, subject: e.target.value })} value={form.subject} /></label>
          </div>
          <label className="field-label">How can we help?<textarea onChange={(e) => setForm({ ...form, message: e.target.value })} required rows={5} value={form.message} /></label>
          <button className="button button-primary" disabled={submitting} type="submit">{submitting ? 'Sending…' : 'Send message'} <ArrowUpRight size={16} /></button>
          <p className="privacy-note">Your details are only used to respond to your inquiry.</p>
        </form>
      </div>
    </main>
  )
}
