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
export const HABIT_ICONS = [
    'run',
    'book',
    'dumbbell',
    'droplet',
    'leaf',
    'moon',
    'heart',
    'target',
    'guitar',
    'brush',
    'notebook',
    'monitor',
] as const
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

export interface Equipment {
    id: number
    name: string
    hasWeight: boolean
    hasDistance: boolean
    isActive: boolean
}

export interface EquipmentPayload {
    name: string
    hasWeight: boolean
    hasDistance: boolean
}

export interface MuscleGroup {
    id: number
    name: string
    isActive: boolean
}

export interface Muscle {
    id: number
    name: string
    isActive: boolean
    muscleGroupId: number
    muscleGroupName: string
    /** A muscle is offered to new movements only when its group is active too. */
    muscleGroupIsActive: boolean
}

export interface MusclePayload {
    name: string
    muscleGroupId: number
}

export interface MovementFamily {
    id: number
    name: string
    isActive: boolean
}

/**
 * A common movement. Its family, muscles and equipments each carry their own status: a movement
 * keeps what was retired after it took it on, and the list has to be able to say so.
 */
export interface Movement {
    id: number
    name: string
    description: string | null
    videoUrl: string | null
    movementFamilyId: number
    movementFamilyName: string
    movementFamilyIsActive: boolean
    primaryMuscle: Muscle
    /** By name. */
    secondaryMuscles: Muscle[]
    /** By name; empty for a bodyweight movement. */
    equipments: Equipment[]
    tracksReps: boolean
    tracksWeight: boolean
    tracksDuration: boolean
    tracksDistance: boolean
    isUnilateral: boolean
    isActive: boolean
}

export interface MovementPayload {
    name: string
    description: string | null
    videoUrl: string | null
    movementFamilyId: number
    primaryMuscleId: number
    secondaryMuscleIds: number[]
    equipmentIds: number[]
    tracksReps: boolean
    tracksWeight: boolean
    tracksDuration: boolean
    tracksDistance: boolean
    isUnilateral: boolean
}

export type Violations = Record<string, string[]>

export const HYDRATION_ICONS = ['glass', 'bottle', 'mug', 'can', 'carafe', 'soda_cup'] as const

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
