import { useState } from 'react'
import { BarChart3, CalendarDays, ClipboardList, Coffee, CreditCard, ExternalLink, FileText, LayoutDashboard, LogOut, Mail, Menu, CircleHelp, Phone, UserRound, Users, X } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import type { PropsWithChildren } from 'react'
import { useAuth } from '../auth/useAuth'

interface SiteLayoutProps extends PropsWithChildren {
  notice: string
  onDismissNotice: () => void
}

const links = [
  { to: '/', label: 'Home' },
  { to: '/membership', label: 'Membership' },
  { to: '/events', label: 'Events' },
  { to: '/blog', label: 'Blog' },
  { to: '/testimonials', label: 'Testimonials' },
  { to: '/about', label: 'About Us' },
  { to: '/core-team', label: 'Core Team' },
  { to: '/contact', label: 'Contact' },
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
      { to: '/directory', label: 'Member directory', icon: Users },
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
    || ['/profile', '/directory', '/support', '/membership/apply', '/account/finances'].includes(location.pathname)
  )

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
            {(notice || error || logoutError) && (
              <div className={`notice ${notice ? 'notice-success' : 'notice-error'}`} role="status">
                <div className="dashboard-notice-inner">
                  <span>{notice || error || logoutError}</span>
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
        <div className="site-utility-bar">
          <div className="page-shell site-utility-inner">
            <div className="site-contact-links">
              <a href="tel:+919624597597"><Phone size={13} /> +91 96245 97597</a>
              <a href="mailto:info@bnpcafe.com"><Mail size={13} /> info@bnpcafe.com</a>
            </div>
            <div className="site-utility-actions">
              <Link onClick={() => setMenuOpen(false)} to="/contact"><CircleHelp size={13} /> Contact Us</Link>
              <Link className="utility-login" onClick={() => setMenuOpen(false)} to={user ? '/account' : '/login'}>
                <UserRound size={13} /> {user ? 'Dashboard' : 'Login'}
              </Link>
            </div>
          </div>
        </div>
        <div className="site-nav-row">
          <div className="page-shell site-nav-inner">
            <Link aria-label="BNP Cafe home" className="brand" to="/" onClick={() => setMenuOpen(false)}>
              <span className="brand-mark brand-monogram">B</span>
              <span className="brand-name">BNP Cafe</span>
            </Link>
            <button
              aria-expanded={menuOpen}
              aria-label={menuOpen ? 'Close navigation menu' : 'Open navigation menu'}
              className="menu-toggle"
              onClick={() => setMenuOpen((open) => !open)}
              type="button"
            >
              {menuOpen ? <X size={22} /> : <Menu size={22} />}
            </button>
            <nav aria-label="Main navigation" className={`main-nav ${menuOpen ? 'main-nav-open' : ''}`}>
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
              <Link className="button button-small button-accent nav-join-button" onClick={() => setMenuOpen(false)} to="/membership">
                Join BNP Cafe
              </Link>
            </nav>
          </div>
        </div>
      </header>
      {(notice || error || logoutError) && (
        <div className={`notice ${notice ? 'notice-success' : 'notice-error'}`} role="status">
          <div className="page-shell flex items-center justify-between gap-4 py-3">
            <span>{notice || error || logoutError}</span>
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
        <div className="page-shell footer-inner">
          <Link aria-label="BNP Cafe home" className="brand brand-footer" to="/">
            <span className="brand-mark"><Coffee size={18} /></span>
            <span className="brand-name">BNP <span>Cafe</span></span>
          </Link>
          <p>Good conversations build good business.</p>
          <span className="footer-copy">© {new Date().getFullYear()} BNP Cafe</span>
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
