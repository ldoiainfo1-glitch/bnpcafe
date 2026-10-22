import axios from 'axios'
import { useCallback, useEffect, useMemo, useState } from 'react'
import type { PropsWithChildren } from 'react'
import { api, prepareSessionRequest } from '../lib/api'
import type { ApiItem, CurrentUser } from '../lib/api'
import { AuthContext } from './context'

export function AuthProvider({ children }: PropsWithChildren) {
  const [user, setUser] = useState<CurrentUser | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const refresh = useCallback(async () => {
    try {
      const response = await api.get<ApiItem<CurrentUser>>('/api/user')
      setUser(response.data.data)
      setError('')
    } catch (requestError) {
      if (axios.isAxiosError(requestError) && requestError.response?.status === 401) {
        setUser(null)
        setError('')
        return
      }

      setError('We could not connect to the platform. Please try again.')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    let active = true
    api.get<ApiItem<CurrentUser>>('/api/user')
      .then((response) => {
        if (active) setUser(response.data.data)
      })
      .catch((requestError: unknown) => {
        if (!active) return
        if (axios.isAxiosError(requestError) && requestError.response?.status === 401) {
          setUser(null)
          return
        }
        setError('We could not connect to the platform. Please try again.')
      })
      .finally(() => {
        if (active) setLoading(false)
      })
    return () => { active = false }
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    await prepareSessionRequest()
    const response = await api.post<ApiItem<CurrentUser>>('/api/auth/login', { email, password })
    setUser(response.data.data)
    setError('')
  }, [])

  const startEmailAccess = useCallback(async (name: string, email: string) => {
    await prepareSessionRequest()
    await api.post('/api/guest/access', { name, email })
  }, [])

  const verifyEmailAccess = useCallback(async (email: string, code: string) => {
    await prepareSessionRequest()
    const response = await api.post<ApiItem<CurrentUser>>('/api/guest/access/verify', { email, code })
    setUser(response.data.data)
    setError('')
  }, [])

  const logout = useCallback(async () => {
    await prepareSessionRequest()
    await api.post('/api/auth/logout')
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({ user, loading, error, login, startEmailAccess, verifyEmailAccess, logout, refresh }),
    [user, loading, error, login, startEmailAccess, verifyEmailAccess, logout, refresh],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
