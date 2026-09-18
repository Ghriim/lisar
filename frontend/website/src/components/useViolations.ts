import { ApiError } from '../api/client'

export interface Violations {
    /** The error codes the API returned for one field, ready for a Field to render. */
    for: (field: string) => string[]
    /** True when the API refused without naming a field: nothing to show under an input. */
    isGeneral: boolean
}

/**
 * Turns whatever a mutation threw into something a form can render. Without it every screen
 * rewrites the same `error instanceof ApiError ? … : []`.
 */
export function useViolations(failure: unknown): Violations {
    const error = failure instanceof ApiError ? failure : null

    return {
        for: (field: string) => error?.violationsFor(field) ?? [],
        isGeneral: error !== null && error.violations === null,
    }
}
