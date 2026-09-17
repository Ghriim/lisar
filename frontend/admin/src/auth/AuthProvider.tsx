import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { refreshSession, setAccessToken } from '../api/client'
import { NotAnAdministratorError } from '../api/errors'
import * as api from '../api/endpoints'
import { ROLE_ADMIN, type User } from '../api/types'
import { AuthContext, type AuthStatus, type AuthValue } from './AuthContext'

export function AuthProvider({ children }: { children: ReactNode }) {
    const [status, setStatus] = useState<AuthStatus>('restoring')
    const [user, setUser] = useState<User | null>(null)

    useEffect(() => {
        let cancelled = false

        const restore = async () => {
            try {
                if (await refreshSession()) {
                    const restored = await api.fetchCurrentUser()

                    // The refresh cookie is shared with the website, so a session restored here
                    // may well belong to someone who is not an administrator.
                    if (!cancelled && restored.role === ROLE_ADMIN) {
                        setUser(restored)
                        setStatus('authenticated')

                        return
                    }

                    setAccessToken(null)
                }
            } catch {
                // Nothing to restore; the sign-in form is the right answer.
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
        const signedIn = await api.fetchCurrentUser()

        if (signedIn.role !== ROLE_ADMIN) {
            // Checked here rather than letting every page collect a 403: the person deserves to
            // be told why, once, at the door.
            await api.signOut()

            throw new NotAnAdministratorError()
        }

        setUser(signedIn)
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
