import { useState } from 'react'

export type ActiveStatus = 'active' | 'inactive' | 'all'

/**
 * The filter's state, opening on active only, and its reading as the API's `isActive`: undefined
 * for all, since "all" is the absence of a filter rather than a third value.
 */
export function useActiveFilter() {
    const [status, setStatus] = useState<ActiveStatus>('active')

    return { status, setStatus, isActive: 'all' === status ? undefined : 'active' === status }
}
