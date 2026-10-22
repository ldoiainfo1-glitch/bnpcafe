import { useState } from 'react'
import { ArrowRight, KeyRound, Mail, ShieldCheck } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { apiErrorMessage } from '../lib/api'

interface LoginPageProps {
  onNotice: (message: string) => void
}

type SignInMethod = 'password' | 'code'

export function LoginPage({ onNotice }: LoginPageProps) {
  const { login, startEmailAccess, verifyEmailAccess } = useAuth()
  const navigate = useNavigate()
  const [method, setMethod] = useState<SignInMethod>('password')
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [code, setCode] = useState('')
  const [codeSent, setCodeSent] = useState(false)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  function changeMethod(nextMethod: SignInMethod) {
    setMethod(nextMethod)
    setError('')
  }

  async function submitPassword(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await login(email, password)
      onNotice('Welcome back. You’re signed in.')
      navigate('/account')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'We could not sign you in. Check your details and try again.'))
    } finally {
      setSubmitting(false)
    }
  }

  async function requestCode(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await startEmailAccess(name, email)
      setCodeSent(true)
      onNotice('If your details can be verified, a sign-in code will be sent to your email.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'The sign-in code could not be requested. Please try again.'))
    } finally {
      setSubmitting(false)
    }
  }

  async function confirmCode(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await verifyEmailAccess(email, code)
      onNotice('Your email is verified. Welcome to BNP Cafe.')
      navigate('/account')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'That code could not be verified. Request a new one and try again.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="signin-layout">
      <section className="signin-story">
        <div className="signin-story-content">
          <p className="eyebrow eyebrow-light"><span className="eyebrow-dot" /> Your community is right here</p>
          <h1>Good to see<br />you <em>again.</em></h1>
          <p>Sign in to reconnect with your chapter, keep up with events, and make your membership count.</p>
          <div className="signin-promise"><ShieldCheck size={18} /><span>Your account is protected with secure sign-in.</span></div>
        </div>
        <span aria-hidden="true" className="signin-decoration">B</span>
      </section>
      <section className="signin-panel">
        <div className="signin-form-wrap">
          <p className="eyebrow">Welcome to BNP Cafe</p>
          <h2>Sign in to your account</h2>
          <p className="signin-subtitle">Use your password or request a one-time email code.</p>
          <div aria-label="Sign-in method" className="auth-tabs" role="tablist">
            <button aria-selected={method === 'password'} className={method === 'password' ? 'auth-tab active' : 'auth-tab'} onClick={() => changeMethod('password')} role="tab" type="button"><KeyRound size={15} /> Password</button>
            <button aria-selected={method === 'code'} className={method === 'code' ? 'auth-tab active' : 'auth-tab'} onClick={() => changeMethod('code')} role="tab" type="button"><Mail size={15} /> Email code</button>
          </div>
          {error && <p className="inline-error" role="alert">{error}</p>}
          {method === 'password' ? (
            <form className="auth-form" onSubmit={submitPassword}>
              <label className="field-label">Email address<input autoComplete="email" onChange={(e) => setEmail(e.target.value)} required type="email" value={email} /></label>
              <label className="field-label">Password<input autoComplete="current-password" onChange={(e) => setPassword(e.target.value)} required type="password" value={password} /></label>
              <button className="button button-primary button-full" disabled={submitting} type="submit">{submitting ? 'Signing in…' : 'Sign in'} <ArrowRight size={16} /></button>
            </form>
          ) : codeSent ? (
            <form className="auth-form" onSubmit={confirmCode}>
              <div className="code-sent-note"><Mail size={18} /><span>Enter the six-digit code sent to <strong>{email}</strong>.</span></div>
              <label className="field-label">Verification code<input autoComplete="one-time-code" inputMode="numeric" maxLength={6} onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))} pattern="[0-9]{6}" required value={code} /></label>
              <button className="button button-primary button-full" disabled={submitting} type="submit">{submitting ? 'Verifying…' : 'Verify and sign in'} <ArrowRight size={16} /></button>
              <button className="text-link resend-button" onClick={() => setCodeSent(false)} type="button">Use a different email or request again</button>
            </form>
          ) : (
            <form className="auth-form" onSubmit={requestCode}>
              <label className="field-label">Your name<input autoComplete="name" onChange={(e) => setName(e.target.value)} required value={name} /></label>
              <label className="field-label">Email address<input autoComplete="email" onChange={(e) => setEmail(e.target.value)} required type="email" value={email} /></label>
              <button className="button button-primary button-full" disabled={submitting} type="submit">{submitting ? 'Requesting code…' : 'Email me a sign-in code'} <ArrowRight size={16} /></button>
              <p className="auth-hint">New to BNP Cafe? Verifying your email creates a Guest account so you can get started.</p>
            </form>
          )}
        </div>
      </section>
    </main>
  )
}
