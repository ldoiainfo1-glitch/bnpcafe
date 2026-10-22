import { useState } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { useAuth } from './auth/useAuth'
import { SiteLayout } from './components/SiteLayout'
import { AccountPage } from './pages/AccountPage'
import { AdminApplicationsPage } from './pages/AdminApplicationsPage'
import { AdminContentPage } from './pages/AdminContentPage'
import { AdminEventsPage } from './pages/AdminEventsPage'
import { AdminReportsPage } from './pages/AdminReportsPage'
import { AdminSupportPage } from './pages/AdminSupportPage'
import { ContactPage } from './pages/ContactPage'
import { ContentPage } from './pages/ContentPage'
import { DirectoryPage } from './pages/DirectoryPage'
import { EventsPage } from './pages/EventsPage'
import { HomePage } from './pages/HomePage'
import { LoginPage } from './pages/LoginPage'
import { MembershipApplyPage } from './pages/MembershipApplyPage'
import { MembershipPlansPage } from './pages/MembershipPlansPage'
import { PaymentCenterPage } from './pages/PaymentCenterPage'
import { ProfilePage } from './pages/ProfilePage'
import { AboutPage, BlogPage, CoreTeamPage, TestimonialsPage } from './pages/PublicInformationPages'
import { SupportPage } from './pages/SupportPage'

function AppRoutes() {
  const { user, loading } = useAuth()
  const [notice, setNotice] = useState('')

  return (
    <SiteLayout notice={notice} onDismissNotice={() => setNotice('')}>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/blog" element={<BlogPage />} />
        <Route path="/testimonials" element={<TestimonialsPage />} />
        <Route path="/core-team" element={<CoreTeamPage />} />
        <Route path="/events" element={<EventsPage onNotice={setNotice} />} />
        <Route path="/membership" element={<MembershipPlansPage />} />
        <Route path="/membership/apply" element={<AuthenticatedRoute><MembershipApplyPage onNotice={setNotice} /></AuthenticatedRoute>} />
        <Route path="/account/finances" element={<AuthenticatedRoute><PaymentCenterPage onNotice={setNotice} /></AuthenticatedRoute>} />
        <Route path="/profile" element={<AuthenticatedRoute><ProfilePage onNotice={setNotice} /></AuthenticatedRoute>} />
        <Route path="/directory" element={<AuthenticatedRoute><DirectoryPage /></AuthenticatedRoute>} />
        <Route path="/support" element={<AuthenticatedRoute><SupportPage onNotice={setNotice} /></AuthenticatedRoute>} />
        <Route path="/admin/applications" element={<AdminRoute><AdminApplicationsPage onNotice={setNotice} /></AdminRoute>} />
        <Route path="/admin/events" element={<AdminRoute><AdminEventsPage onNotice={setNotice} /></AdminRoute>} />
        <Route path="/admin/support" element={<AdminRoute><AdminSupportPage onNotice={setNotice} /></AdminRoute>} />
        <Route path="/admin/reports" element={<AdminRoute><AdminReportsPage /></AdminRoute>} />
        <Route path="/admin/content" element={<SuperAdminRoute><AdminContentPage onNotice={setNotice} /></SuperAdminRoute>} />
        <Route path="/contact" element={<ContactPage onNotice={setNotice} />} />
        <Route path="/pages/:slug" element={<ContentPage />} />
        <Route
          path="/login"
          element={
            loading ? (
              <LoadingPage />
            ) : user ? (
              <Navigate to="/account" replace />
            ) : (
              <LoginPage onNotice={setNotice} />
            )
          }
        />
        <Route
          path="/account"
          element={
            loading ? (
              <LoadingPage />
            ) : user ? (
              <AccountPage onNotice={setNotice} />
            ) : (
              <Navigate to="/login" replace />
            )
          }
        />
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </SiteLayout>
  )
}

function AuthenticatedRoute({ children }: React.PropsWithChildren) {
  const { user, loading } = useAuth()
  if (loading) return <LoadingPage />
  return user ? children : <Navigate to="/login" replace />
}

function AdminRoute({ children }: React.PropsWithChildren) {
  const { user, loading } = useAuth()
  if (loading) return <LoadingPage />
  if (!user) return <Navigate to="/login" replace />
  return user.role === 'super_admin' || user.role === 'sub_admin'
    ? children
    : <Navigate to="/account" replace />
}

function SuperAdminRoute({ children }: React.PropsWithChildren) {
  const { user, loading } = useAuth()
  if (loading) return <LoadingPage />
  if (!user) return <Navigate to="/login" replace />
  return user.role === 'super_admin' ? children : <Navigate to="/account" replace />
}

function LoadingPage() {
  return (
    <main className="page-shell grid min-h-[50vh] place-items-center">
      <p className="text-sm text-slate-500" role="status">Loading your account…</p>
    </main>
  )
}

function NotFoundPage() {
  return (
    <main className="page-shell py-24 text-center">
      <p className="eyebrow">Page not found</p>
      <h1 className="mt-4 text-4xl font-semibold">This page has moved on.</h1>
      <a className="button button-primary mt-8" href="/">Return home</a>
    </main>
  )
}

function App() {
  return (
    <AuthProvider>
      <AppRoutes />
    </AuthProvider>
  )
}

export default App
