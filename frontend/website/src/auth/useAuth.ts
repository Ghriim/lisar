import { useContext } from 'react'
import { AuthContext, type AuthValue } from './AuthContext'

export function useAuth(): AuthValue {
    const value = useContext(AuthContext)

    if (value === null) {
        throw new Error('useAuth must be used inside an AuthProvider.')
    }

    return value
}
