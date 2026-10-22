import { useEffect, useState } from 'react'
import { ArrowRight, BarChart3, CalendarDays, CircleHelp, ClipboardList, CreditCard, FileText, KeyRound, Mail, UserRound, Users } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { api, apiErrorMessage } from '../lib/api'

interface AccountPageProps {
  onNotice: (message: string) => void
}

interface MemberDashboard {
  role: string
  applications: Array<{ id: number; chapter: string; plan: string; status: string; created_at: string }>
  memberships: Array<{ id: number; chapter: string; plan: string; status: string; starts_at: string | null; ends_at: string | null }>
  upcoming_events: Array<{ id: number; title: string; venue: string | null; starts_at: string | null }>
  profile_complete?: boolean
  can_apply_for_membership?: boolean
  open_support_tickets?: number
}

interface AdminDashboard {
  role: string
  applications: Record<string, number>
  active_members: number
  upcoming_events: number
  upcoming_event_registrations: number
  open_support_tickets: number
  new_inquiries: number | null
}

interface PasswordForm {
  current_password: string
  password: string
  password_confirmation: string
}

export function AccountPage({ onNotice }: AccountPageProps) {
  const { user } = useAuth()
  const [dashboard, setDashboard] = useState<MemberDashboard | AdminDashboard | null>(null)
  const [error, setError] = useState('')
  const [passwordError, setPasswordError] = useState('')
  const [loading, setLoading] = useState(true)
  const [savingPassword, setSavingPassword] = useState(false)
  const [password, setPassword] = useState<PasswordForm>({ current_password: '', password: '', password_confirmation: '' })

  useEffect(() => {
    let active = true
    api.get<{ data: MemberDashboard | AdminDashboard }>('/api/dashboard')
      .then((response) => { if (active) setDashboard(response.data.data) })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Your dashboard is temporarily unavailable.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function updatePassword(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setPasswordError('')
    setSavingPassword(true)
    try {
      await api.patch('/api/auth/password', password)
      setPassword({ current_password: '', password: '', password_confirmation: '' })
      onNotice('Your password has been updated.')
    } catch (requestError) {
      setPasswordError(apiErrorMessage(requestError, 'Your password could not be changed.'))
    } finally {
      setSavingPassword(false)
    }
  }

  const isAdmin = user?.role === 'super_admin' || user?.role === 'sub_admin'
  const memberDashboard = dashboard && 'memberships' in dashboard ? dashboard : null
  const adminDashboard = dashboard && 'active_members' in dashboard ? dashboard : null

  return (
    <main className="page-shell account-page">
      <section className="account-welcome">
        <div>
          <p className="eyebrow">{isAdmin ? 'ADMIN WORKSPACE' : user?.role === 'member' ? 'MEMBER DASHBOARD' : 'GUEST DASHBOARD'}</p>
          <h1>Welcome, <em>{user?.name.split(' ')[0]}.</em></h1>
          <p>{dashboardDescription(user?.role ?? '')}</p>
        </div>
        <span className="role-badge"><UserRound size={15} />{formatRole(user?.role || '')}</span>
      </section>
      {error && <p className="inline-error" role="status">{error}</p>}
      {loading ? (
        <p className="loading-copy" role="status">Loading your account…</p>
      ) : isAdmin ? (
        <AdminOverview dashboard={adminDashboard} role={user?.role ?? 'sub_admin'} />
      ) : user?.role === 'guest' ? (
        <GuestOverview dashboard={memberDashboard} />
      ) : (
        <MemberOverview dashboard={memberDashboard} />
      )}
      <div className="account-grid">
        <section className="account-card">
          <div className="account-card-heading"><span className="account-card-icon"><KeyRound size={18} /></span><div><h2>Account password</h2><p>Keep your sign-in secure.</p></div></div>
          <form className="account-password-form" onSubmit={updatePassword}>
            {user?.password_set && (
              <label className="field-label">Current password<input autoComplete="current-password" onChange={(e) => setPassword({ ...password, current_password: e.target.value })} required type="password" value={password.current_password} /></label>
            )}
            <label className="field-label">New password<input autoComplete="new-password" minLength={12} onChange={(e) => setPassword({ ...password, password: e.target.value })} required type="password" value={password.password} /></label>
            <label className="field-label">Confirm new password<input autoComplete="new-password" onChange={(e) => setPassword({ ...password, password_confirmation: e.target.value })} required type="password" value={password.password_confirmation} /></label>
            {passwordError && <p className="inline-error" role="alert">{passwordError}</p>}
            <button className="button button-outline-dark" disabled={savingPassword} type="submit">{savingPassword ? 'Saving…' : user?.password_set ? 'Change password' : 'Set password'}</button>
          </form>
        </section>
        <section className="account-card account-shortcuts">
          <div className="account-card-heading"><span className="account-card-icon"><CircleHelp size={18} /></span><div><h2>Keep exploring</h2><p>Your community is ready when you are.</p></div></div>
          <Link className="account-shortcut" to="/profile"><span><UserRound size={18} /> Update my business profile</span><ArrowRight size={16} /></Link>
          {!isAdmin && <Link className="account-shortcut" to="/account/finances"><span><CreditCard size={18} /> Payments and invoices</span><ArrowRight size={16} /></Link>}
          <Link className="account-shortcut" to="/events"><span><CalendarDays size={18} /> Browse upcoming events</span><ArrowRight size={16} /></Link>
          {!isAdmin && (
            <>
              <Link className="account-shortcut" to="/membership/apply"><span><CreditCard size={18} /> Apply for membership</span><ArrowRight size={16} /></Link>
              {user?.role === 'member' && <Link className="account-shortcut" to="/directory"><span><UserRound size={18} /> Member directory</span><ArrowRight size={16} /></Link>}
            </>
          )}
          <Link className="account-shortcut" to="/support"><span><CircleHelp size={18} /> Get support</span><ArrowRight size={16} /></Link>
          <Link className="account-shortcut" to="/contact"><span><CircleHelp size={18} /> Contact the team</span><ArrowRight size={16} /></Link>
          {isAdmin && (
            <>
              <Link className="account-shortcut" to="/admin/applications"><span><CreditCard size={18} /> Review applications</span><ArrowRight size={16} /></Link>
              <Link className="account-shortcut" to="/admin/events"><span><CalendarDays size={18} /> Manage events and attendance</span><ArrowRight size={16} /></Link>
              <Link className="account-shortcut" to="/admin/support"><span><CircleHelp size={18} /> Review support requests</span><ArrowRight size={16} /></Link>
              <Link className="account-shortcut" to="/admin/reports"><span><CreditCard size={18} /> Operational reports</span><ArrowRight size={16} /></Link>
              {user?.role === 'super_admin' && <Link className="account-shortcut" to="/admin/content"><span><UserRound size={18} /> Manage public content</span><ArrowRight size={16} /></Link>}
            </>
          )}
        </section>
      </div>
    </main>
  )
}

function MemberOverview({ dashboard }: { dashboard: MemberDashboard | null }) {
  if (!dashboard) return <div className="empty-panel"><p>Your dashboard could not be loaded.</p></div>
  return (
    <div className="account-sections">
      <section className="dashboard-feature-card member-feature-card">
        <div>
          <p className="eyebrow">YOUR NETWORK, AT A GLANCE</p>
          <h2>Build your next <em>connection.</em></h2>
          <p>Keep your membership current, discover fellow members, and make the most of upcoming chapter events.</p>
        </div>
        <Link className="button dashboard-feature-button" to="/directory"><Users size={17} /> Explore member directory</Link>
      </section>
      <div className="account-summary-grid">
        <SummaryCard label="Memberships" value={dashboard.memberships.length} icon={<CreditCard size={19} />} />
        <SummaryCard label="Applications" value={dashboard.applications.length} icon={<UserRound size={19} />} />
        <SummaryCard label="Upcoming events" value={dashboard.upcoming_events.length} icon={<CalendarDays size={19} />} />
      </div>
      <div className="account-data-grid">
        <section className="account-card">
          <div className="account-card-heading"><div><h2>Your memberships</h2><p>Membership status and renewal dates.</p></div><Link className="text-link" to="/membership">View plans</Link></div>
          {dashboard.memberships.length ? dashboard.memberships.map((membership) => (
            <div className="data-row" key={membership.id}><span><strong>{membership.plan}</strong><small>{membership.chapter}</small></span><span className={`status-pill status-${membership.status}`}>{humanize(membership.status)}</span></div>
          )) : <p className="empty-inline">Your membership journey can start here.</p>}
        </section>
        <section className="account-card">
          <div className="account-card-heading"><div><h2>Recent applications</h2><p>Updates on your membership requests.</p></div></div>
          {dashboard.applications.length ? dashboard.applications.slice(0, 5).map((application) => (
            <div className="data-row" key={application.id}><span><strong>{application.plan}</strong><small>{application.chapter}</small></span><span className={`status-pill status-${application.status}`}>{humanize(application.status)}</span></div>
          )) : <p className="empty-inline">You don’t have any applications yet.</p>}
        </section>
      </div>
      <section className="account-card">
        <div className="account-card-heading"><div><h2>Coming up</h2><p>Upcoming public events from your chapters.</p></div><Link className="text-link" to="/events">All events</Link></div>
        {dashboard.upcoming_events.length ? (
          <div className="upcoming-grid">{dashboard.upcoming_events.map((event) => <div className="upcoming-item" key={event.id}><CalendarDays size={17} /><div><strong>{event.title}</strong><small>{event.starts_at ? formatDate(event.starts_at) : 'Date to be announced'}{event.venue ? ` · ${event.venue}` : ''}</small></div></div>)}</div>
        ) : <p className="empty-inline">No upcoming events are listed yet.</p>}
      </section>
    </div>
  )
}

function GuestOverview({ dashboard }: { dashboard: MemberDashboard | null }) {
  if (!dashboard) return <div className="empty-panel"><p>Your dashboard could not be loaded.</p></div>
  return (
    <div className="account-sections">
      <section className="dashboard-feature-card guest-feature-card">
        <div>
          <p className="eyebrow">YOUR BNP CAFE JOURNEY STARTS HERE</p>
          <h2>Turn a good introduction into a <em>great opportunity.</em></h2>
          <p>Complete your application to join a chapter, connect with local business leaders, and access member benefits.</p>
        </div>
        <Link className="button dashboard-feature-button" to="/membership/apply"><ArrowRight size={17} /> Start membership application</Link>
      </section>
      <div className="account-summary-grid">
        <SummaryCard label="Applications in progress" value={dashboard.applications.length} icon={<ClipboardList size={19} />} />
        <SummaryCard label="Upcoming community events" value={dashboard.upcoming_events.length} icon={<CalendarDays size={19} />} />
        <SummaryCard label="Support requests" value={dashboard.open_support_tickets ?? 0} icon={<CircleHelp size={19} />} />
      </div>
      <section className="account-card guest-next-steps">
        <div className="account-card-heading"><div><h2>Your next steps</h2><p>A few simple actions help you get started.</p></div></div>
        <div className="guest-step-grid">
          <Link className="guest-step" to="/profile"><span>01</span><strong>Complete your business profile</strong><small>Share your business details for your application.</small></Link>
          <Link className="guest-step" to="/membership/apply"><span>02</span><strong>Choose a chapter and plan</strong><small>Send your application to the right local team.</small></Link>
          <Link className="guest-step" to="/events"><span>03</span><strong>Meet the community</strong><small>Find a public event and introduce yourself.</small></Link>
        </div>
      </section>
      <section className="account-card">
        <div className="account-card-heading"><div><h2>Upcoming events</h2><p>Meet your local business community.</p></div><Link className="text-link" to="/events">Browse events</Link></div>
        {dashboard.upcoming_events.length ? (
          <div className="upcoming-grid">{dashboard.upcoming_events.map((event) => <div className="upcoming-item" key={event.id}><CalendarDays size={17} /><div><strong>{event.title}</strong><small>{event.starts_at ? formatDate(event.starts_at) : 'Date to be announced'}{event.venue ? ` · ${event.venue}` : ''}</small></div></div>)}</div>
        ) : <p className="empty-inline">No upcoming events are listed yet. Check back soon.</p>}
      </section>
    </div>
  )
}

function AdminOverview({ dashboard, role }: { dashboard: AdminDashboard | null; role: string }) {
  if (!dashboard) return <div className="empty-panel"><p>Your dashboard could not be loaded.</p></div>
  const isSuperAdmin = role === 'super_admin'
  return (
    <div className="account-sections">
      <section className={`dashboard-feature-card ${isSuperAdmin ? 'super-admin-feature-card' : 'sub-admin-feature-card'}`}>
        <div>
          <p className="eyebrow">{isSuperAdmin ? 'PLATFORM OVERVIEW' : 'YOUR ASSIGNED CHAPTERS'}</p>
          <h2>{isSuperAdmin ? <>Your platform, in <em>good hands.</em></> : <>Your chapter, moving <em>forward.</em></>}</h2>
          <p>{isSuperAdmin
            ? 'Monitor member growth, applications, events, and support across the BNP Cafe network.'
            : 'Track applications, events, and support requests for chapters assigned to you.'}</p>
        </div>
        <Link className="button dashboard-feature-button" to="/admin/applications"><ClipboardList size={17} /> Review applications</Link>
      </section>
      <div className="account-summary-grid admin-summary-grid">
        <SummaryCard label={isSuperAdmin ? 'Active members platform-wide' : 'Active members in my chapters'} value={dashboard.active_members} icon={<Users size={19} />} />
        <SummaryCard label="Applications awaiting review" value={(dashboard.applications.submitted ?? 0) + (dashboard.applications.under_review ?? 0)} icon={<CreditCard size={19} />} />
        <SummaryCard label="Upcoming chapter events" value={dashboard.upcoming_events} icon={<CalendarDays size={19} />} />
        <SummaryCard label="Open support requests" value={dashboard.open_support_tickets} icon={<CircleHelp size={19} />} />
        {isSuperAdmin && dashboard.new_inquiries !== null && <SummaryCard label="New contact inquiries" value={dashboard.new_inquiries} icon={<Mail size={19} />} />}
      </div>
      <section className="account-card admin-work-queue">
        <div className="account-card-heading"><div><h2>Operations at a glance</h2><p>{isSuperAdmin ? 'Choose a platform area to review or manage.' : 'Your dashboard data is limited to chapters assigned to you.'}</p></div></div>
        <div className="admin-quick-actions">
          <Link className="admin-quick-action" to="/admin/applications"><ClipboardList size={21} /><span><strong>Membership applications</strong><small>{dashboard.applications.submitted ?? 0} submitted · {dashboard.applications.under_review ?? 0} under review</small></span><ArrowRight size={17} /></Link>
          <Link className="admin-quick-action" to="/admin/events"><CalendarDays size={21} /><span><strong>Events &amp; attendance</strong><small>{dashboard.upcoming_event_registrations} upcoming registrations across {dashboard.upcoming_events} events</small></span><ArrowRight size={17} /></Link>
          <Link className="admin-quick-action" to="/admin/support"><CircleHelp size={21} /><span><strong>Support requests</strong><small>{dashboard.open_support_tickets} open requests need attention</small></span><ArrowRight size={17} /></Link>
          <Link className="admin-quick-action" to="/admin/reports"><BarChart3 size={21} /><span><strong>{isSuperAdmin ? 'Platform reports' : 'Chapter reports'}</strong><small>Review activity and performance over time</small></span><ArrowRight size={17} /></Link>
          {isSuperAdmin && <Link className="admin-quick-action" to="/admin/content"><FileText size={21} /><span><strong>Public website content</strong><small>Manage published information pages</small></span><ArrowRight size={17} /></Link>}
        </div>
      </section>
    </div>
  )
}

function SummaryCard({ label, value, icon }: { label: string; value: number; icon: React.ReactNode }) {
  return <article className="summary-card"><span>{icon}</span><div><strong>{value}</strong><small>{label}</small></div></article>
}

function formatRole(role: string) {
  return role.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}

function dashboardDescription(role: string) {
  if (role === 'super_admin') return 'A platform-wide view of members, applications, events, and operations.'
  if (role === 'sub_admin') return 'Manage chapter activity and support your assigned local community.'
  if (role === 'member') return 'Your membership, upcoming events, and connections in one place.'
  return 'Explore the community and take your first step toward membership.'
}

function humanize(value: string) {
  return value.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}

function formatDate(value: string) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Date to be announced' : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date)
}
