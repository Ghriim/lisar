import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { refreshSession } from '../api/client'
import * as api from '../api/endpoints'
import type { User } from '../api/types'
import { AuthContext, type AuthStatus, type AuthValue } from './AuthContext'

export function AuthProvider({ children }: { children: ReactNode }) {
    const [status, setStatus] = useState<AuthStatus>('restoring')
    const [user, setUser] = useState<User | null>(null)

    /**
     * On the first paint there is no access token — it only ever lived in memory. If the browser
     * still holds the refresh cookie, this gets a new one and the person never sees a login form.
     */
    useEffect(() => {
        let cancelled = false

        const restore = async () => {
            try {
                if (await refreshSession()) {
                    const restored = await api.fetchCurrentUser()

                    if (!cancelled) {
                        setUser(restored)
                        setStatus('authenticated')

                        return
                    }
                }
            } catch {
                // Nothing to restore; the login form is the right answer.
            }

            if (!cancelled) {
                setStatus('anonymous')
            }
        }

        void restore()

        return () => {
            cancelled = true
        }
    }, [])

    const signIn = useCallback(async (email: string, password: string) => {
        await api.signIn(email, password)
        setUser(await api.fetchCurrentUser())
        setStatus('authenticated')
    }, [])

    const signOut = useCallback(async () => {
        await api.signOut()
        setUser(null)
        setStatus('anonymous')
    }, [])

    const value = useMemo<AuthValue>(
        () => ({ status, user, signIn, signOut }),
        [status, user, signIn, signOut],
    )

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
