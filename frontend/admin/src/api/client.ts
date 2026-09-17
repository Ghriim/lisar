import type { Session, Violations } from './types'

/**
 * The access token lives here and nowhere else: not in localStorage, not in a cookie this code
 * can read. A reload loses it, and the httpOnly refresh cookie silently gets a new one.
 */
let accessToken: string | null = null

export function setAccessToken(token: string | null): void {
    accessToken = token
}

export class ApiError extends Error {
    // Declared rather than promoted: this project builds with erasableSyntaxOnly.
    readonly status: number
    readonly violations: Violations | null
    readonly code: string | null

    constructor(status: number, violations: Violations | null = null, code: string | null = null) {
        super(`API error ${status}`)

        this.name = 'ApiError'
        this.status = status
        this.violations = violations
        this.code = code
    }

    violationsFor(field: string): string[] {
        return this.violations?.[field] ?? []
    }
}

interface RequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
    body?: unknown
    /** Set on the calls that must not trigger a refresh: signing in, refreshing, signing out. */
    skipRefresh?: boolean
}

async function send(path: string, options: RequestOptions): Promise<Response> {
    const headers: Record<string, string> = {}

    if (options.body !== undefined) {
        headers['Content-Type'] = 'application/json'
    }
    if (accessToken !== null) {
        headers.Authorization = `Bearer ${accessToken}`
    }

    return fetch(path, {
        method: options.method ?? 'GET',
        headers,
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
        credentials: 'include',
    })
}

async function toError(response: Response): Promise<ApiError> {
    let violations: Violations | null = null
    let code: string | null = null

    try {
        const payload = (await response.json()) as { violations?: Violations | string; message?: string }

        if (payload.violations !== undefined && typeof payload.violations !== 'string') {
            violations = payload.violations
        }
        if (typeof payload.message === 'string') {
            code = payload.message
        }
    } catch {
        // A body that is not JSON tells us nothing the status did not.
    }

    return new ApiError(response.status, violations, code)
}

export async function refreshSession(): Promise<boolean> {
    const response = await send('/api/auth/refresh', { method: 'POST', skipRefresh: true })

    if (!response.ok) {
        accessToken = null

        return false
    }

    const session = (await response.json()) as Session
    accessToken = session.accessToken

    return true
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    let response = await send(path, options)

    // One silent retry: a 15-minute token expires constantly under a back-office open all day.
    if (response.status === 401 && options.skipRefresh !== true) {
        if (await refreshSession()) {
            response = await send(path, options)
        }
    }

    if (!response.ok) {
        throw await toError(response)
    }

    if (response.status === 204) {
        return undefined as T
    }

    return (await response.json()) as T
}
