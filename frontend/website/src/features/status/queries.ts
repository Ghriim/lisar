import { useQuery } from '@tanstack/react-query'
import * as api from '../../api/endpoints'

/** The status' key: whatever earns experience, once it does, re-reads it. */
export const STATUS = ['status'] as const

export function useStatus() {
    return useQuery({
        queryKey: STATUS,
        queryFn: api.fetchStatus,
    })
}
