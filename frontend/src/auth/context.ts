import { createContext } from 'react'
import type { CurrentUser } from '../lib/api'

export interface AuthContextValue {
  user: CurrentUser | null
  loading: boolean
  error: string
  login: (email: string, password: string) => Promise<void>
  startEmailAccess: (name: string, email: string) => Promise<void>
  verifyEmailAccess: (email: string, code: string) => Promise<void>
  logout: () => Promise<void>
  refresh: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)
