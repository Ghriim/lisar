import { createContext } from 'react'
import type { User } from '../api/types'

export type AuthStatus = 'restoring' | 'authenticated' | 'anonymous'

export interface AuthValue {
    status: AuthStatus
    user: User | null
    signIn: (email: string, password: string) => Promise<void>
    signOut: () => Promise<void>
}

export const AuthContext = createContext<AuthValue | null>(null)
