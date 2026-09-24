import { request, setAccessToken } from './client'
import type {
    Category,
    Habit,
    HabitPayload,
    HydrationPreset,
    HydrationPresetPayload,
    Page,
    Priority,
    PriorityPayload,
    Session,
    User,
    UserComment,
} from './types'

export async function signIn(email: string, password: string): Promise<Session> {
    const session = await request<Session>('/api/auth/login', {
        method: 'POST',
        body: { email, password },
        skipRefresh: true,
    })

    setAccessToken(session.accessToken)

    return session
}

export async function signOut(): Promise<void> {
    try {
        await request<void>('/api/auth/logout', { method: 'POST', skipRefresh: true })
    } finally {
        setAccessToken(null)
    }
}

export function fetchCurrentUser(): Promise<User> {
    return request<User>('/api/users/me')
}

export interface UserFilters {
    search?: string
    isActive?: boolean
    page: number
    perPage: number
}

export function fetchUsers(filters: UserFilters): Promise<Page<User>> {
    const query = new URLSearchParams({
        page: String(filters.page),
        perPage: String(filters.perPage),
    })

    if (filters.search !== undefined && filters.search !== '') {
        query.set('search', filters.search)
    }
    if (filters.isActive !== undefined) {
        query.set('isActive', String(filters.isActive))
    }

    return request<Page<User>>(`/api/admin/users?${query.toString()}`)
}

export function fetchUser(id: number): Promise<User> {
    return request<User>(`/api/admin/users/${id}`)
}

export function activateUser(id: number): Promise<User> {
    return request<User>(`/api/admin/users/${id}/activate`, { method: 'POST' })
}

export function deactivateUser(id: number): Promise<User> {
    return request<User>(`/api/admin/users/${id}/deactivate`, { method: 'POST' })
}

export function fetchUserComments(id: number): Promise<UserComment[]> {
    return request<UserComment[]>(`/api/admin/users/${id}/comments`)
}

export function createUserComment(id: number, body: string): Promise<UserComment> {
    return request<UserComment>(`/api/admin/users/${id}/comments`, { method: 'POST', body: { body } })
}

export function fetchPriorities(): Promise<Priority[]> {
    return request<Priority[]>('/api/admin/priorities')
}

export function createPriority(payload: PriorityPayload): Promise<Priority> {
    return request<Priority>('/api/admin/priorities', { method: 'POST', body: payload })
}

export function updatePriority(id: number, payload: PriorityPayload): Promise<Priority> {
    return request<Priority>(`/api/admin/priorities/${id}`, { method: 'PUT', body: payload })
}

export function deletePriority(id: number): Promise<void> {
    return request<void>(`/api/admin/priorities/${id}`, { method: 'DELETE' })
}

export function fetchCategories(): Promise<Category[]> {
    return request<Category[]>('/api/admin/categories')
}

export function createCategory(label: string): Promise<Category> {
    return request<Category>('/api/admin/categories', { method: 'POST', body: { label } })
}

export function updateCategory(id: number, label: string): Promise<Category> {
    return request<Category>(`/api/admin/categories/${id}`, { method: 'PUT', body: { label } })
}

export function deleteCategory(id: number): Promise<void> {
    return request<void>(`/api/admin/categories/${id}`, { method: 'DELETE' })
}

export function fetchHydrationPresets(): Promise<HydrationPreset[]> {
    return request<HydrationPreset[]>('/api/admin/hydration/presets')
}

export function createHydrationPreset(payload: HydrationPresetPayload): Promise<HydrationPreset> {
    return request<HydrationPreset>('/api/admin/hydration/presets', { method: 'POST', body: payload })
}

export function updateHydrationPreset(
    id: number,
    payload: HydrationPresetPayload,
): Promise<HydrationPreset> {
    return request<HydrationPreset>(`/api/admin/hydration/presets/${id}`, { method: 'PUT', body: payload })
}

export function fetchHabits(isActive?: boolean): Promise<Habit[]> {
    const query = isActive === undefined ? '' : `?isActive=${isActive ? 'true' : 'false'}`

    return request<Habit[]>(`/api/admin/habits${query}`)
}

export function createHabit(payload: HabitPayload): Promise<Habit> {
    return request<Habit>('/api/admin/habits', { method: 'POST', body: payload })
}

export function updateHabit(id: number, payload: HabitPayload): Promise<Habit> {
    return request<Habit>(`/api/admin/habits/${id}`, { method: 'PUT', body: payload })
}

export function activateHabit(id: number): Promise<Habit> {
    return request<Habit>(`/api/admin/habits/${id}/activate`, { method: 'POST' })
}

export function deactivateHabit(id: number): Promise<Habit> {
    return request<Habit>(`/api/admin/habits/${id}/deactivate`, { method: 'POST' })
}

export function deleteHydrationPreset(id: number): Promise<void> {
    return request<void>(`/api/admin/hydration/presets/${id}`, { method: 'DELETE' })
}
