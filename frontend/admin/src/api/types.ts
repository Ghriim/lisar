/** The API contract, transcribed. Every shape here mirrors a DataOutput on the back end. */

export const ROLE_ADMIN = 'ROLE_ADMIN'

export interface User {
    id: number
    username: string
    email: string
    avatar: string | null
    isActive: boolean
    role: string
    lastSignedInAt: string | null
    createdAt: string | null
    updatedAt: string | null
}

export interface Session {
    accessToken: string
    expiresIn: number
    tokenType: string
}

/** The envelope every paginated list answers with. */
export interface Page<TItem> {
    items: TItem[]
    total: number
    page: number
    perPage: number
}

export interface UserComment {
    id: number
    body: string
    authorId: number
    authorUsername: string
    createdAt: string | null
}

export interface Priority {
    id: number
    label: string
    colour: string
    weight: number
    isDefault: boolean
}

export interface Category {
    id: number
    label: string
    isPersonal: boolean
}

export interface PriorityPayload {
    label: string
    weight: number
    colour: string
    isDefault: boolean
}

/** The habit-catalogue vocabularies, mirroring the backend registries. Kept in sync by hand. */
export const HABIT_ICONS = ['run', 'book', 'dumbbell', 'droplet', 'leaf', 'moon', 'heart', 'target'] as const
export const HABIT_SOURCES = ['manual', 'tracker'] as const
export const HABIT_TRACKERS = ['steps', 'hydration'] as const

export interface Habit {
    id: number
    name: string
    icon: string
    sourceKind: string
    trackerKind: string | null
    trackerThreshold: number | null
    isActive: boolean
}

export interface HabitPayload {
    name: string
    icon: string
    sourceKind: string
    trackerKind: string | null
    trackerThreshold: number | null
}

export type Violations = Record<string, string[]>

export const HYDRATION_ICONS = ['glass', 'bottle', 'mug', 'can', 'carafe'] as const

export type HydrationIcon = (typeof HYDRATION_ICONS)[number]

export interface HydrationPreset {
    id: number
    icon: string
    volumeInMillilitres: number
}

export interface HydrationPresetPayload {
    icon: string
    volumeInMillilitres: number
}
