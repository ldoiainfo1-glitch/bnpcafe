import { useState } from 'react'
import { BarChart3, CalendarDays, ClipboardList, Coffee, CreditCard, ExternalLink, FileText, LayoutDashboard, LogOut, Menu, CircleHelp, UserRound, Users, X } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import type { PropsWithChildren } from 'react'
import { useAuth } from '../auth/useAuth'

interface SiteLayoutProps extends PropsWithChildren {
  notice: string
  onDismissNotice: () => void
}

const links = [
  { to: '/about', label: 'About' },
  { to: '/membership', label: 'Membership' },
  { to: '/chapters', label: 'Chapters' },
  { to: '/events', label: 'Events' },
  { to: '/directory', label: 'Directory' },
  { to: '/stories', label: 'Stories' },
  { to: '/partners', label: 'Partners' },
  { to: '/faq', label: 'FAQ' },
]

interface DashboardNavigationItem {
  to: string
  label: string
  icon: LucideIcon
}

function dashboardNavigation(role: string): DashboardNavigationItem[] {
  const overview = { to: '/account', label: 'Overview', icon: LayoutDashboard }

  if (role === 'super_admin') {
    return [
      overview,
      { to: '/admin/applications', label: 'Membership applications', icon: ClipboardList },
      { to: '/admin/events', label: 'Events & attendance', icon: CalendarDays },
      { to: '/admin/support', label: 'Support requests', icon: CircleHelp },
      { to: '/admin/reports', label: 'Reports & analytics', icon: BarChart3 },
      { to: '/admin/content', label: 'Website content', icon: FileText },
      { to: '/profile', label: 'My profile', icon: UserRound },
    ]
  }

  if (role === 'sub_admin') {
    return [
      overview,
      { to: '/admin/applications', label: 'Chapter applications', icon: ClipboardList },
      { to: '/admin/events', label: 'Events & attendance', icon: CalendarDays },
      { to: '/admin/support', label: 'Support requests', icon: CircleHelp },
      { to: '/admin/reports', label: 'Chapter reports', icon: BarChart3 },
      { to: '/profile', label: 'My profile', icon: UserRound },
    ]
  }

  if (role === 'member') {
    return [
      overview,
      { to: '/account/finances', label: 'Payments & invoices', icon: CreditCard },
      { to: '/membership/apply', label: 'Membership', icon: ClipboardList },
      { to: '/account/directory', label: 'Member directory', icon: Users },
      { to: '/events', label: 'Events', icon: CalendarDays },
      { to: '/profile', label: 'My business profile', icon: UserRound },
      { to: '/support', label: 'Get support', icon: CircleHelp },
    ]
  }

  return [
    overview,
    { to: '/membership/apply', label: 'Apply for membership', icon: ClipboardList },
    { to: '/events', label: 'Events', icon: CalendarDays },
    { to: '/profile', label: 'My profile', icon: UserRound },
    { to: '/support', label: 'Get support', icon: CircleHelp },
  ]
}

export function SiteLayout({ children, notice, onDismissNotice }: SiteLayoutProps) {
  const { user, logout, error } = useAuth()
  const [menuOpen, setMenuOpen] = useState(false)
  const [logoutError, setLogoutError] = useState('')
  const navigate = useNavigate()
  const location = useLocation()
  const isDashboardRoute = Boolean(user) && (
    location.pathname === '/account'
    || location.pathname.startsWith('/admin/')
    || ['/profile', '/account/directory', '/support', '/membership/apply', '/account/finances'].includes(location.pathname)
  )
  const visibleAuthError = isDashboardRoute || location.pathname === '/login' ? error : ''

  async function handleLogout() {
    setLogoutError('')
    try {
      await logout()
      setMenuOpen(false)
      navigate('/')
    } catch {
      setLogoutError('We could not sign you out. Please try again.')
    }
  }

  return (
    <div className={`min-h-screen${isDashboardRoute ? ' dashboard-app' : ''}`}>
      {isDashboardRoute && user ? (
        <div className="dashboard-layout">
          <aside className="dashboard-sidebar">
            <Link aria-label="BNP Cafe home" className="dashboard-brand" to="/">
              <span className="dashboard-brand-mark"><Coffee size={21} /></span>
              <span>BNP <em>Cafe</em></span>
            </Link>
            <div className="dashboard-profile">
              <span className="dashboard-avatar">{user.name.trim().charAt(0).toUpperCase()}</span>
              <span className="dashboard-user-copy">
                <strong>{user.name}</strong>
                <small>{formatRole(user.role)}</small>
              </span>
            </div>
            <p className="dashboard-nav-heading">WORKSPACE</p>
            <nav aria-label={`${formatRole(user.role)} dashboard navigation`} className="dashboard-nav">
              {dashboardNavigation(user.role).map(({ to, label, icon: Icon }) => (
                <NavLink
                  className={({ isActive }) => `dashboard-nav-link${isActive ? ' dashboard-nav-link-active' : ''}`}
                  end={to === '/account'}
                  key={to}
                  to={to}
                >
                  <Icon aria-hidden="true" size={19} strokeWidth={1.8} />
                  <span>{label}</span>
                </NavLink>
              ))}
            </nav>
            <div className="dashboard-sidebar-bottom">
              <Link className="dashboard-nav-link" to="/">
                <ExternalLink aria-hidden="true" size={19} strokeWidth={1.8} />
                <span>Visit public website</span>
              </Link>
              <button className="dashboard-nav-link dashboard-signout" onClick={() => void handleLogout()} type="button">
                <LogOut aria-hidden="true" size={19} strokeWidth={1.8} />
                <span>Sign out</span>
              </button>
            </div>
          </aside>
          <div className="dashboard-main">
            {(notice || visibleAuthError || logoutError) && (
              <div className={`notice ${notice ? 'notice-success' : 'notice-error'}`} role="status">
                <div className="dashboard-notice-inner">
                  <span>{notice || visibleAuthError || logoutError}</span>
                  {notice && <button aria-label="Dismiss notification" onClick={onDismissNotice} type="button"><X size={18} /></button>}
                </div>
              </div>
            )}
            {children}
          </div>
        </div>
      ) : (
        <>
      <header className="site-header">
        <div className="site-nav-row">
          <div className="page-shell site-nav-inner reference-site-nav-inner">
            <Link aria-label="BNP Cafe home" className="brand" to="/" onClick={() => setMenuOpen(false)}>
              <img alt="" className="brand-logo" src="/bnp-cafe-logo.png" />
              <span className="brand-name">BNP Cafe</span>
            </Link>
            <button
              aria-expanded={menuOpen}
              aria-label={menuOpen ? 'Close menu' : 'Menu'}
              className="menu-toggle reference-menu-toggle"
              onClick={() => setMenuOpen((open) => !open)}
              type="button"
            >
              {menuOpen ? <X size={22} /> : <Menu size={22} />}
            </button>
            <nav aria-label="Main navigation" className={`main-nav reference-main-nav${menuOpen ? ' reference-main-nav-open' : ''}`}>
              {links.map((link) => (
                <NavLink
                  className={({ isActive }) => `nav-link${isActive && (link.to !== '/' || location.pathname === '/') ? ' nav-link-active' : ''}`}
                  end={link.to === '/'}
                  key={link.to}
                  onClick={() => setMenuOpen(false)}
                  to={link.to}
                >
                  {link.label}
                </NavLink>
              ))}
              <Link className="button button-accent nav-join-button-mobile" onClick={() => setMenuOpen(false)} to="/login">Join BNP CAFE</Link>
            </nav>
            <Link className="button button-accent nav-join-button" onClick={() => setMenuOpen(false)} to="/login">Join BNP CAFE</Link>
          </div>
        </div>
      </header>
      {(notice || visibleAuthError || logoutError) && (
        <div className={`notice ${notice ? 'notice-success' : 'notice-error'}`} role="status">
          <div className="page-shell flex items-center justify-between gap-4 py-3">
            <span>{notice || visibleAuthError || logoutError}</span>
            {notice && (
              <button aria-label="Dismiss notification" onClick={onDismissNotice} type="button">
                <X size={18} />
              </button>
            )}
          </div>
        </div>
      )}
      {children}
      <footer className="site-footer">
        <div className="page-shell footer-inner reference-footer-inner">
          <div className="reference-footer-brand">
            <Link aria-label="BNP CAFE home" className="brand brand-footer" to="/">
              <img alt="" className="brand-logo" src="/bnp-cafe-logo.png" />
            </Link>
            <p>Brew Connections. Build Trust. Grow Business.</p>
          </div>
          <div className="reference-footer-group">
            <p>Explore</p>
            <Link to="/">Home</Link>
            <Link to="/about">About</Link>
            <Link to="/membership">Membership</Link>
            <Link to="/chapters">Chapters</Link>
            <Link to="/events">Events</Link>
            <Link to="/directory">Directory</Link>
            <Link to="/stories">Success Stories</Link>
          </div>
          <div className="reference-footer-group">
            <p>For Members</p>
            <Link to="/login">Login</Link>
            <Link to="/account/directory">Member Directory</Link>
            <Link to="/events">Events</Link>
            <Link to="/faq">FAQs</Link>
          </div>
          <div className="reference-footer-group">
            <p>For Businesses</p>
            <Link to="/partners">Partner With Us</Link>
            <Link to="/partners">Start a Chapter</Link>
            <Link to="/partners">Corporate Networking</Link>
          </div>
          <div className="reference-footer-bottom">
            <span>Connect. Collaborate. Refer. Grow.</span>
            <span>© BNP CAFE. All Rights Reserved.</span>
          </div>
        </div>
      </footer>
        </>
      )}
    </div>
  )
}

function formatRole(role: string) {
  return role.split('_').map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`).join(' ')
}
