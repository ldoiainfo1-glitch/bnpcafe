import axios from 'axios'

const defaultApiUrl = `${window.location.protocol}//${window.location.hostname}:8000`
const apiBaseUrl = (import.meta.env.VITE_API_URL || defaultApiUrl).replace(/\/$/, '')

export const api = axios.create({
  baseURL: apiBaseUrl,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export async function prepareSessionRequest() {
  await axios.get(`${apiBaseUrl}/sanctum/csrf-cookie`, {
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
  })
}

export function apiErrorMessage(error: unknown, fallback: string): string {
  if (axios.isAxiosError<{ message?: string; errors?: Record<string, string[]> }>(error)) {
    const firstFieldError = Object.values(error.response?.data?.errors ?? {})[0]?.[0]
    return firstFieldError ?? error.response?.data?.message ?? fallback
  }

  return fallback
}

export interface ApiPage<T> {
  data: T[]
  links?: Record<string, string | null>
  meta?: { current_page: number; last_page: number; total: number }
}

export interface ApiItem<T> {
  data: T
}

export interface SiteSettings {
  [key: string]: string
}

export interface EventSummary {
  id: number
  chapter: { id: number; name: string } | null
  title: string
  description: string
  venue: string | null
  starts_at: string | null
  ends_at: string | null
  registration_closes_at: string | null
  capacity: number | null
  status: string
}

export interface ChapterSummary {
  id: number
  pincode: string
  name: string
  state: string | null
  about: string | null
}

export interface MembershipPlan {
  id: number
  name: string
  description: string | null
  price: string
  currency: string
  duration_months: number
  features: string[]
  is_active: boolean
}

export interface MemberProfile {
  phone: string | null
  business_name: string | null
  industry: string | null
  designation: string | null
  business_address: string | null
  bio: string | null
  website: string | null
  directory_email: string | null
  directory_phone: string | null
  show_in_directory: boolean
}

export interface ContentSummary {
  id: number
  slug: string
  title: string
  summary: string | null
  body: string
  status: string
}

export interface CurrentUser {
  id: number
  name: string
  email: string
  role: 'super_admin' | 'sub_admin' | 'member' | 'guest'
  chapter_id: number | null
  password_set: boolean
}

export interface ContactInquiryInput {
  name: string
  email: string
  phone?: string
  subject?: string
  message: string
}

export interface EventRegistrationInput {
  name: string
  email: string
  phone?: string
  business_name?: string
}
