import { useEffect, useState } from 'react'
import { ArrowRight, Check, Sparkles } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiPage, MembershipPlan } from '../lib/api'

export function MembershipPlansPage() {
  const [plans, setPlans] = useState<MembershipPlan[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiPage<MembershipPlan>>('/api/membership-plans', { params: { per_page: 50 } })
      .then((response) => { if (active) setPlans(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Membership options are unavailable at the moment.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  return (
    <main className="page-shell page-content">
      <div className="page-intro">
        <p className="eyebrow">Find your place at the table</p>
        <h1>Membership made for <em>real growth.</em></h1>
        <p>Choose a way to get involved, connect with fellow business owners, and make the most of your local community.</p>
      </div>
      {error && <p className="inline-error" role="status">{error}</p>}
      {loading ? (
        <p className="loading-copy" role="status">Loading membership options…</p>
      ) : plans.length ? (
        <div className="plan-grid">
          {plans.map((plan, index) => (
            <article className={`plan-card ${index === 1 ? 'plan-card-featured' : ''}`} key={plan.id}>
              {index === 1 && <span className="plan-ribbon"><Sparkles size={14} /> Popular choice</span>}
              <p className="eyebrow">{plan.duration_months} month{plan.duration_months === 1 ? '' : 's'}</p>
              <h2>{plan.name}</h2>
              <p className="plan-description">{plan.description || 'Join the BNP Cafe community and start building meaningful connections.'}</p>
              <p className="plan-price">{formatPrice(plan.price, plan.currency)}<span> / {plan.duration_months} months</span></p>
              <ul className="plan-features">
                {plan.features.map((feature) => <li key={feature}><Check size={16} />{feature}</li>)}
              </ul>
              <Link className={`button ${index === 1 ? 'button-accent' : 'button-primary'} plan-action`} to="/login">
                Get started <ArrowRight size={16} />
              </Link>
            </article>
          ))}
        </div>
      ) : (
        <div className="empty-panel"><Sparkles size={24} /><p>Membership options are being refreshed.</p><span>Contact us and we’ll help you find the best way to join.</span><Link className="text-link" to="/contact">Talk with our team <ArrowRight size={15} /></Link></div>
      )}
      <div className="plans-note"><p>Have questions about membership?</p><Link className="text-link" to="/contact">We’re happy to help <ArrowRight size={15} /></Link></div>
    </main>
  )
}

function formatPrice(price: string, currency: string) {
  const amount = Number(price)
  if (!Number.isFinite(amount)) return `${currency} ${price}`
  return new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount)
}
