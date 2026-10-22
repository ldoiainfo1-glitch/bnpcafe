import { useEffect, useState } from 'react'
import { ArrowLeft, Check, Eye, EyeOff, Save, UserRound } from 'lucide-react'
import { Link } from 'react-router-dom'
import { api, apiErrorMessage } from '../lib/api'
import type { ApiItem, MemberProfile } from '../lib/api'

interface ProfilePageProps {
  onNotice: (message: string) => void
}

const emptyProfile: MemberProfile = {
  phone: '',
  business_name: '',
  industry: '',
  designation: '',
  business_address: '',
  bio: '',
  website: '',
  directory_email: '',
  directory_phone: '',
  show_in_directory: false,
}

export function ProfilePage({ onNotice }: ProfilePageProps) {
  const [profile, setProfile] = useState<MemberProfile>(emptyProfile)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api.get<ApiItem<MemberProfile | null>>('/api/profile')
      .then((response) => {
        if (active && response.data.data) setProfile({ ...emptyProfile, ...response.data.data })
      })
      .catch((requestError: unknown) => {
        if (active) setError(apiErrorMessage(requestError, 'Your profile could not be loaded.'))
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  async function saveProfile(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setSaving(true)
    try {
      const response = await api.patch<ApiItem<MemberProfile>>('/api/profile', profile)
      setProfile({ ...emptyProfile, ...response.data.data })
      onNotice('Your profile has been saved.')
    } catch (requestError) {
      setError(apiErrorMessage(requestError, 'Your profile could not be saved.'))
    } finally {
      setSaving(false)
    }
  }

  function update<K extends keyof MemberProfile>(key: K, value: MemberProfile[K]) {
    setProfile((current) => ({ ...current, [key]: value }))
  }

  if (loading) return <main className="page-shell page-content"><p className="loading-copy">Loading your profile…</p></main>

  return (
    <main className="page-shell page-content profile-page">
      <Link className="text-link back-link" to="/account"><ArrowLeft size={15} /> Back to your dashboard</Link>
      <div className="page-intro">
        <p className="eyebrow">Your details, your choice</p>
        <h1>Make your profile <em>your own.</em></h1>
        <p>Keep your business details up to date. You control what, if anything, appears in the member directory.</p>
      </div>
      <form className="form-card profile-form" onSubmit={saveProfile}>
        <div className="form-heading"><span className="form-step"><UserRound size={17} /></span><div><h2>Business profile</h2><p>Your account email is kept private.</p></div></div>
        {error && <p className="inline-error" role="alert">{error}</p>}
        <div className="form-row">
          <label className="field-label">Phone<input autoComplete="tel" onChange={(e) => update('phone', e.target.value)} value={profile.phone ?? ''} /></label>
          <label className="field-label">Business name<input autoComplete="organization" onChange={(e) => update('business_name', e.target.value)} value={profile.business_name ?? ''} /></label>
        </div>
        <div className="form-row">
          <label className="field-label">Industry<input onChange={(e) => update('industry', e.target.value)} value={profile.industry ?? ''} /></label>
          <label className="field-label">Your role<input onChange={(e) => update('designation', e.target.value)} value={profile.designation ?? ''} /></label>
        </div>
        <label className="field-label">Business address<textarea onChange={(e) => update('business_address', e.target.value)} rows={3} value={profile.business_address ?? ''} /></label>
        <label className="field-label">Short introduction<textarea maxLength={2000} onChange={(e) => update('bio', e.target.value)} rows={4} value={profile.bio ?? ''} /></label>
        <label className="field-label">Website<input autoComplete="url" onChange={(e) => update('website', e.target.value)} placeholder="https://" type="url" value={profile.website ?? ''} /></label>
        <section className="directory-consent">
          <div className="directory-consent-head">
            <span className="account-card-icon">{profile.show_in_directory ? <Eye size={18} /> : <EyeOff size={18} />}</span>
            <div><h3>Member directory</h3><p>Your account email and private contact details are never listed.</p></div>
          </div>
          <label className="checkbox-row"><input checked={profile.show_in_directory} onChange={(e) => update('show_in_directory', e.target.checked)} type="checkbox" /><span>Show my business profile to eligible members</span></label>
          {profile.show_in_directory && (
            <div className="form-row directory-contact-fields">
              <label className="field-label">Directory email<input autoComplete="email" onChange={(e) => update('directory_email', e.target.value)} type="email" value={profile.directory_email ?? ''} /></label>
              <label className="field-label">Directory phone<input autoComplete="tel" onChange={(e) => update('directory_phone', e.target.value)} value={profile.directory_phone ?? ''} /></label>
            </div>
          )}
          <p className="privacy-callout"><Check size={14} /> Only active members who opt in are visible in the directory.</p>
        </section>
        <button className="button button-primary" disabled={saving} type="submit"><Save size={15} />{saving ? 'Saving…' : 'Save profile'}</button>
      </form>
    </main>
  )
}
