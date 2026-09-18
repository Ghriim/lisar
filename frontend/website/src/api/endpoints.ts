import { request, setAccessToken } from './client'
import type {
    Category,
    CreateTaskPayload,
    HydrationDay,
    HydrationPreset,
    Priority,
    Session,
    Task,
    UpdateTaskPayload,
    User,
    Weight,
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
        // Whatever the server answered, this tab is done with that token.
        setAccessToken(null)
    }
}

export function register(username: string, email: string, password: string): Promise<User> {
    return request<User>('/api/users/register', {
        method: 'POST',
        body: { username, email, password },
        skipRefresh: true,
    })
}

export function fetchCurrentUser(): Promise<User> {
    return request<User>('/api/users/me')
}

export function fetchTasks(isDone: boolean): Promise<Task[]> {
    return request<Task[]>(`/api/tasks?isDone=${isDone ? 'true' : 'false'}`)
}

export function fetchTask(id: number): Promise<Task> {
    return request<Task>(`/api/tasks/${id}`)
}

export function createTask(payload: CreateTaskPayload): Promise<Task> {
    return request<Task>('/api/tasks', { method: 'POST', body: payload })
}

export function updateTask(id: number, payload: UpdateTaskPayload): Promise<Task> {
    return request<Task>(`/api/tasks/${id}`, { method: 'PUT', body: payload })
}

export function completeTask(id: number): Promise<Task> {
    return request<Task>(`/api/tasks/${id}/complete`, { method: 'POST' })
}

export function reopenTask(id: number): Promise<Task> {
    return request<Task>(`/api/tasks/${id}/reopen`, { method: 'POST' })
}

export function deleteTask(id: number): Promise<void> {
    return request<void>(`/api/tasks/${id}`, { method: 'DELETE' })
}

export function fetchPriorities(): Promise<Priority[]> {
    return request<Priority[]>('/api/priorities')
}

export function fetchCategories(): Promise<Category[]> {
    return request<Category[]>('/api/categories')
}

export function fetchTags(): Promise<string[]> {
    return request<string[]>('/api/tags')
}

export function createCategory(label: string): Promise<Category> {
    return request<Category>('/api/categories', { method: 'POST', body: { label } })
}

export function updateCategory(id: number, label: string): Promise<Category> {
    return request<Category>(`/api/categories/${id}`, { method: 'PUT', body: { label } })
}

export function deleteCategory(id: number): Promise<void> {
    return request<void>(`/api/categories/${id}`, { method: 'DELETE' })
}

export function fetchHydrationToday(): Promise<HydrationDay> {
    return request<HydrationDay>('/api/hydration/today')
}

export function fetchHydrationPresets(): Promise<HydrationPreset[]> {
    return request<HydrationPreset[]>('/api/hydration/presets')
}

/** Every write answers with the whole day: one round trip refreshes the total and the goal. */
export function createHydrationEntry(volumeInMillilitres: number): Promise<HydrationDay> {
    return request<HydrationDay>('/api/hydration/entries', {
        method: 'POST',
        body: { volumeInMillilitres },
    })
}

export function updateHydrationEntry(id: number, volumeInMillilitres: number): Promise<HydrationDay> {
    return request<HydrationDay>(`/api/hydration/entries/${id}`, {
        method: 'PUT',
        body: { volumeInMillilitres },
    })
}

export function deleteHydrationEntry(id: number): Promise<HydrationDay> {
    return request<HydrationDay>(`/api/hydration/entries/${id}`, { method: 'DELETE' })
}

export function fetchLatestWeight(): Promise<Weight> {
    return request<Weight>('/api/weight/latest')
}

/**
 * A PUT on the day in progress: there is one weight per day, so recording twice writes the same
 * thing twice — which is what PUT means. No day is ever sent; the server knows which one it is.
 */
export function saveWeight(weightInKilograms: number): Promise<Weight> {
    return request<Weight>('/api/weight/today', {
        method: 'PUT',
        body: { weightInKilograms },
    })
}
