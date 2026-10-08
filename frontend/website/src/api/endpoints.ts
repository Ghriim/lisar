import { request, setAccessToken } from './client'
import type {
    Category,
    CreateTaskPayload,
    Habit,
    HabitCatalogItem,
    HydrationDay,
    HydrationPreset,
    Page,
    PersonalBestBoard,
    Priority,
    Session,
    SetType,
    SleepNight,
    StepDay,
    Task,
    UpdateTaskPayload,
    User,
    Weight,
    Workout,
    WorkoutBlockExercisePayload,
    WorkoutCopy,
    WorkoutDetailsPayload,
    WorkoutMovementChoice,
    WorkoutPreviousPerformance,
    WorkoutSetPayload,
    WorkoutStats,
    WorkoutSummary,
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

export function fetchStepsToday(): Promise<StepDay> {
    return request<StepDay>('/api/steps/today')
}

/**
 * A PUT on the day in progress, like the weight: one count per day, and it is cumulative, so
 * recording twice writes the same thing twice — which is what PUT means. No day is ever sent; the
 * server knows which one it is.
 */
export function saveSteps(countInSteps: number): Promise<StepDay> {
    return request<StepDay>('/api/steps/today', {
        method: 'PUT',
        body: { countInSteps },
    })
}

export function fetchHabits(): Promise<Habit[]> {
    return request<Habit[]>('/api/habits')
}

/** Ticking a manual habit for today answers with that habit, its week refreshed. */
export function completeHabit(id: number): Promise<Habit> {
    return request<Habit>(`/api/habits/${id}/complete`, { method: 'POST' })
}

/** Un-ticking today — the undo of a mis-tap. Answers with the habit, its week refreshed. */
export function uncompleteHabit(id: number): Promise<Habit> {
    return request<Habit>(`/api/habits/${id}/complete`, { method: 'DELETE' })
}

export function fetchHabitCatalog(): Promise<HabitCatalogItem[]> {
    return request<HabitCatalogItem[]>('/api/habits/catalog')
}

export function subscribeHabit(id: number): Promise<HabitCatalogItem> {
    return request<HabitCatalogItem>(`/api/habits/${id}/subscribe`, { method: 'POST' })
}

export function unsubscribeHabit(id: number): Promise<HabitCatalogItem> {
    return request<HabitCatalogItem>(`/api/habits/${id}/subscribe`, { method: 'DELETE' })
}

export function fetchSleepToday(): Promise<SleepNight> {
    return request<SleepNight>('/api/sleep/today')
}

/**
 * A PUT on the day in progress, like the weight: one night per day, so noting it twice writes
 * the same thing twice. No date is ever sent — two times of day say the whole night, and the
 * server works out which day each one falls on.
 */
export function saveSleepNight(
    bedtime: string,
    wakeUpTime: string,
    moodRating: number | null,
): Promise<SleepNight> {
    return request<SleepNight>('/api/sleep/today', {
        method: 'PUT',
        body: { bedtime, wakeUpTime, moodRating },
    })
}

/** Finished workouts only, the latest first: the one in progress joins the history when it ends. */
export function fetchWorkouts(page: number): Promise<Page<WorkoutSummary>> {
    return request<Page<WorkoutSummary>>(`/api/workouts?page=${page}`)
}

/** The workout in progress, or null: the API answers 204 when there is none. */
export async function fetchCurrentWorkout(): Promise<Workout | null> {
    return (await request<Workout | undefined>('/api/workouts/current')) ?? null
}

export function fetchWorkout(id: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}`)
}

export function fetchWorkoutPreviousPerformances(id: number): Promise<WorkoutPreviousPerformance[]> {
    return request<WorkoutPreviousPerformance[]>(`/api/workouts/${id}/previous-performances`)
}

export function fetchPersonalBests(): Promise<PersonalBestBoard> {
    return request<PersonalBestBoard>('/api/workouts/personal-bests')
}

export function fetchWorkoutStats(id: number): Promise<WorkoutStats> {
    return request<WorkoutStats>(`/api/workouts/${id}/stats`)
}

export function fetchWorkoutMovements(): Promise<WorkoutMovementChoice[]> {
    return request<WorkoutMovementChoice[]>('/api/workouts/movements')
}

export function fetchWorkoutSetTypes(): Promise<SetType[]> {
    return request<SetType[]>('/api/workouts/set-types')
}

/*
 * Every write inside a workout — its details, a block, a movement, a set — answers the whole
 * workout, so the screen redraws from one response.
 */

export function startWorkout(name: string | null): Promise<Workout> {
    return request<Workout>('/api/workouts', { method: 'POST', body: { name } })
}

/** A new workout, now, laid out like a finished one: its sets come back still to do. */
export function copyWorkout(id: number): Promise<WorkoutCopy> {
    return request<WorkoutCopy>(`/api/workouts/${id}/copy`, { method: 'POST' })
}

export function updateWorkout(id: number, payload: WorkoutDetailsPayload): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}`, { method: 'PUT', body: payload })
}

export function finishWorkout(id: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/finish`, { method: 'POST' })
}

/** Abandons the one in progress; deletes a finished one. Everything logged in it goes too. */
export function deleteWorkout(id: number): Promise<void> {
    return request<void>(`/api/workouts/${id}`, { method: 'DELETE' })
}

/** Several movements make a superset, in the order given, each with its own rest. */
export function addWorkoutBlock(id: number, exercises: WorkoutBlockExercisePayload[]): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/blocks`, { method: 'POST', body: { exercises } })
}

export function reorderWorkoutBlocks(id: number, blockIds: number[]): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/blocks/order`, { method: 'PUT', body: { blockIds } })
}

export function deleteWorkoutBlock(id: number, blockId: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/blocks/${blockId}`, { method: 'DELETE' })
}

export function addWorkoutExercise(id: number, blockId: number, exercise: WorkoutBlockExercisePayload): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/blocks/${blockId}/exercises`, { method: 'POST', body: exercise })
}

/** Both fields, every time: one left null is cleared. */
export function updateWorkoutExercise(
    id: number,
    exerciseId: number,
    payload: { note: string | null; restInSeconds: number | null },
): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/exercises/${exerciseId}`, { method: 'PUT', body: payload })
}

/** Removing a block's last movement removes the block too. */
export function deleteWorkoutExercise(id: number, exerciseId: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/exercises/${exerciseId}`, { method: 'DELETE' })
}

export function addWorkoutSet(id: number, exerciseId: number, payload: WorkoutSetPayload): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/exercises/${exerciseId}/sets`, { method: 'POST', body: payload })
}

export function updateWorkoutSet(id: number, setId: number, payload: WorkoutSetPayload): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/sets/${setId}`, { method: 'PUT', body: payload })
}

/** Ticks a set of the workout in progress as done. Refused once the workout is finished. */
export function completeWorkoutSet(id: number, setId: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/sets/${setId}/complete`, { method: 'POST' })
}

/** Unticks it — the undo of a mis-tap. Refused once the workout is finished. */
export function uncompleteWorkoutSet(id: number, setId: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/sets/${setId}/complete`, { method: 'DELETE' })
}

export function deleteWorkoutSet(id: number, setId: number): Promise<Workout> {
    return request<Workout>(`/api/workouts/${id}/sets/${setId}`, { method: 'DELETE' })
}
