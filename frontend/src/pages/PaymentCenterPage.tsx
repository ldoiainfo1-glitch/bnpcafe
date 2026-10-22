import { useCallback, useEffect, useState } from 'react'
import { ArrowLeft, CheckCircle2, CreditCard, FileText, Receipt } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage } from '../lib/api'

interface Membership {
  id: number
  status: string
  chapter: { id: number; name: string }
  plan: { id: number; name: string; price: string; currency: string; duration_months: number }
  starts_at: string | null
  ends_at: string | null
}

interface PaymentRecord {
  id: number
  membership_id: number
  provider: string
  amount_minor: number
  currency: string
  status: string
  paid_at: string | null
  invoice: { id: number; number: string } | null
  created_at: string | null
}

interface InvoiceRecord {
  id: number
  number: string
  membership_id: number
  amount_minor: number
  currency: string
  issued_at: string | null
}

interface OrderResponse {
  key_id: string
  order_id: string
  amount: number
  currency: string
  payment_id: number
}

interface RazorpaySuccess {
  razorpay_payment_id: string
  razorpay_order_id: string
  razorpay_signature: string
}

interface RazorpayOptions {
  key: string
  amount: number
  currency: string
  name: string
  description: string
  order_id: string
  prefill: { name?: string; email?: string }
  theme: { color: string }
  handler: (response: RazorpaySuccess) => void
  modal: { ondismiss: () => void }
}

interface RazorpayInstance {
  open: () => void
  on: (event: string, callback: (response: { error?: { description?: string } }) => void) => void
}

declare global {
  interface Window {
    Razorpay?: new (options: RazorpayOptions) => RazorpayInstance
  }
}

interface PaymentCenterPageProps {
  onNotice: (message: string) => void
}

export function PaymentCenterPage({ onNotice }: PaymentCenterPageProps) {
  const { user } = useAuth()
  const [memberships, setMemberships] = useState<Membership[]>([])
  const [payments, setPayments] = useState<PaymentRecord[]>([])
  const [invoices, setInvoices] = useState<InvoiceRecord[]>([])
  const [loading, setLoading] = useState(true)
  const [payingId, setPayingId] = useState<number | null>(null)
  const [error, setError] = useState('')

  const loadFinancialData = useCallback(async () => {
    const [membershipResponse, paymentResponse, invoiceResponse] = await Promise.all([
      api.get<ApiPage<Membership>>('/api/memberships'),
      api.get<ApiPage<PaymentRecord>>('/api/payments'),
      api.get<ApiPage<InvoiceRecord>>('/api/invoices'),
    ])
    setMemberships(membershipResponse.data.data)
    setPayments(paymentResponse.data.data)
    setInvoices(invoiceResponse.data.data)
  }, [])

  useEffect(() => {
    let active = true
    Promise.all([
      api.get<ApiPage<Membership>>('/api/memberships'),
      api.get<ApiPage<PaymentRecord>>('/api/payments'),
      api.get<ApiPage<InvoiceRecord>>('/api/invoices'),
    ])
      .then(([membershipResponse, paymentResponse, invoiceResponse]) => {
        if (!active) return
        setMemberships(membershipResponse.data.data)
        setPayments(paymentResponse.data.data)
        setInvoices(invoiceResponse.data.data)
      })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Your financial records could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function startPayment(membership: Membership) {
    setError('')
    setPayingId(membership.id)
    try {
      const orderResponse = await api.post<OrderResponse>(`/api/memberships/${membership.id}/payments/razorpay/order`)
      await loadRazorpayCheckout()
      const order = orderResponse.data

      if (!window.Razorpay) {
        throw new Error('The Razorpay checkout is unavailable. Please refresh and try again.')
      }

      const checkout = new window.Razorpay({
        key: order.key_id,
        amount: order.amount,
        currency: order.currency,
        name: 'BNP Cafe',
        description: `${membership.plan.name} membership`,
        order_id: order.order_id,
        prefill: { name: user?.name, email: user?.email },
        theme: { color: '#245b4b' },
        handler: (response) => {
          void confirmPayment(membership.id, response)
        },
        modal: {
          ondismiss: () => setPayingId(null),
        },
      })
      checkout.on('payment.failed', (response) => {
        setError(response.error?.description || 'The payment could not be completed. You can safely try again.')
        setPayingId(null)
      })
      checkout.open()
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Payment checkout could not be started.'))
      setPayingId(null)
    }
  }

  async function confirmPayment(membershipId: number, response: RazorpaySuccess) {
    setError('')
    try {
      await api.post(`/api/memberships/${membershipId}/payments/razorpay/confirm`, {
        razorpay_order_id: response.razorpay_order_id,
        razorpay_payment_id: response.razorpay_payment_id,
        razorpay_signature: response.razorpay_signature,
      })
      await loadFinancialData()
      onNotice('Your payment is verified and your membership is active.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'We could not verify the payment yet. Please contact support before trying again.'))
    } finally {
      setPayingId(null)
    }
  }

  if (loading) return <main className="page-shell page-content"><p className="loading-copy">Loading payment history…</p></main>

  return (
    <main className="page-shell page-content finance-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to your dashboard</Link>
      <div className="page-intro"><p className="eyebrow">Your membership account</p><h1>Payments &amp; <em>invoices.</em></h1><p>Review your membership status, payment history, and issued invoices.</p></div>
      {error && <p className="inline-error" role="alert">{error}</p>}
      <section className="finance-section">
        <div className="finance-section-heading"><span><CreditCard size={18} /></span><div><h2>Your memberships</h2><p>Membership payments are processed securely by Razorpay.</p></div></div>
        {memberships.length ? (
          <div className="finance-memberships">
            {memberships.map((membership) => (
              <article className="finance-membership" key={membership.id}>
                <div><span className={`status-pill status-${membership.status}`}>{humanize(membership.status)}</span><h3>{membership.plan.name}</h3><p>{membership.chapter.name} · {formatPrice(membership.plan.price, membership.plan.currency)} · {membership.plan.duration_months} months</p>{membership.ends_at && <small>Valid through {formatDate(membership.ends_at)}</small>}</div>
                {membership.status === 'pending_payment' && <button className="button button-primary" disabled={payingId === membership.id} onClick={() => void startPayment(membership)} type="button">{payingId === membership.id ? 'Opening checkout…' : 'Pay membership fee'} <CreditCard size={15} /></button>}
                {membership.status === 'active' && <span className="payment-active"><CheckCircle2 size={16} /> Membership active</span>}
              </article>
            ))}
          </div>
        ) : <div className="empty-panel"><CreditCard size={22} /><p>No memberships yet.</p><Link className="text-link" to="/membership/apply">Apply for membership <ArrowLeft size={14} /></Link></div>}
      </section>
      <div className="finance-history-grid">
        <section className="finance-section">
          <div className="finance-section-heading"><span><Receipt size={18} /></span><div><h2>Payment history</h2><p>Payments associated with your account.</p></div></div>
          {payments.length ? payments.map((payment) => (
            <div className="finance-record" key={payment.id}><span><strong>{formatMinor(payment.amount_minor, payment.currency)}</strong><small>{payment.provider} · {payment.paid_at ? formatDate(payment.paid_at) : 'Payment attempt'}</small></span><span className={`status-pill status-${payment.status}`}>{humanize(payment.status)}</span></div>
          )) : <p className="empty-inline">No payments have been recorded.</p>}
        </section>
        <section className="finance-section">
          <div className="finance-section-heading"><span><FileText size={18} /></span><div><h2>Invoices</h2><p>Structured invoice records issued for completed payments.</p></div></div>
          {invoices.length ? invoices.map((invoice) => (
            <div className="finance-record" key={invoice.id}><span><strong>{invoice.number}</strong><small>{formatMinor(invoice.amount_minor, invoice.currency)} · {invoice.issued_at ? formatDate(invoice.issued_at) : 'Issued'}</small></span><span className="invoice-json-note">Invoice record</span></div>
          )) : <p className="empty-inline">Invoices appear here after a verified payment.</p>}
        </section>
      </div>
      <p className="finance-disclaimer">BNP Cafe never collects card details. Invoice records are shown here; downloadable invoice documents are not currently available.</p>
    </main>
  )
}

async function loadRazorpayCheckout() {
  if (window.Razorpay) return

  await new Promise<void>((resolve, reject) => {
    const existingScript = document.querySelector<HTMLScriptElement>('script[data-razorpay-checkout]')
    if (existingScript) {
      if (existingScript.dataset.loaded === 'true') return
      existingScript.addEventListener('load', () => resolve(), { once: true })
      existingScript.addEventListener('error', () => reject(new Error('Razorpay checkout failed to load.')), { once: true })
      return
    }

    const script = document.createElement('script')
    script.src = 'https://checkout.razorpay.com/v1/checkout.js'
    script.async = true
    script.dataset.razorpayCheckout = 'true'
    script.onload = () => {
      script.dataset.loaded = 'true'
      resolve()
    }
    script.onerror = () => {
      script.remove()
      reject(new Error('Razorpay checkout failed to load.'))
    }
    document.body.appendChild(script)
  })

  if (!window.Razorpay) {
    throw new Error('Razorpay checkout is unavailable. Please refresh and try again.')
  }
}

function formatMinor(amountMinor: number, currency: string) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amountMinor / 100)
}

function formatPrice(price: string, currency: string) {
  const amount = Number(price)
  return Number.isFinite(amount) ? new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount) : `${currency} ${price}`
}

function formatDate(value: string) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(date)
}

function humanize(value: string) {
  return value.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}
