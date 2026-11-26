import type { Task, TaskPriority, TaskState } from '../../api/types'

interface StateDisplay {
    label: string
    colour: string
}

/** How the System names each state, and the colour it says it in. */
const STATES: Record<TaskState, StateDisplay> = {
    to_do: { label: 'À faire', colour: 'var(--text-dim)' },
    in_progress: { label: 'En cours', colour: 'var(--accent)' },
    ready_to_close: { label: 'Prête à clore', colour: 'var(--success)' },
    done: { label: 'Terminée', colour: 'var(--text-faint)' },
}

export function stateDisplay(state: TaskState): StateDisplay {
    return STATES[state]
}

/** How the System names each priority, and its colour. The codes are the API's, the rest is ours. */
const PRIORITIES: Record<TaskPriority, StateDisplay> = {
    high: { label: 'Haute', colour: '#e5484d' },
    normal: { label: 'Normale', colour: '#3e63dd' },
    low: { label: 'Basse', colour: '#889096' },
}

export function priorityDisplay(priority: TaskPriority): StateDisplay {
    return PRIORITIES[priority]
}

/** Today, as the API writes a day: the comparison has to happen in the same vocabulary. */
export function today(): string {
    const now = new Date()
    const month = `${now.getMonth() + 1}`.padStart(2, '0')
    const day = `${now.getDate()}`.padStart(2, '0')

    return `${now.getFullYear()}-${month}-${day}`
}

export function isOverdue(task: Task): boolean {
    return task.dueDate !== null && task.state !== 'done' && task.dueDate < today()
}

/** "2026-09-30" as a person reads it. */
export function formatDay(day: string): string {
    const [year, month, date] = day.split('-')

    return `${date}/${month}/${year}`
}

/** "2026-09-30" said short, on a row: « 30/09 ». */
export function formatDayShort(day: string): string {
    const [, month, date] = day.split('-')

    return `${date}/${month}`
}

const WEEKDAYS_SHORT = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']

/** A day ahead, with its weekday: « ven. 28/11 ». */
export function formatDayWithWeekday(day: string): string {
    const [year, month, date] = day.split('-').map(Number)

    return `${WEEKDAYS_SHORT[new Date(year, month - 1, date).getDay()]} ${formatDayShort(day)}`
}

/** Today, as the heading of its section says it: « mer. 25 nov. ». */
export function formatTodayHeading(now: Date = new Date()): string {
    return new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }).format(now)
}

/** Whether a task belongs to « Aujourd'hui »: still open, and due today or before. */
export function isForToday(task: Task, day: string = today()): boolean {
    return task.state !== 'done' && task.dueDate !== null && task.dueDate <= day
}

/** A task of today's that was ticked off today — it stays in « Aujourd'hui », ticked. */
export function isDoneToday(task: Task, day: string = today()): boolean {
    return task.state === 'done' && task.dueDate !== null && task.dueDate <= day && task.completedAt !== null && task.completedAt.slice(0, 10) === day
}

/** « 1 tâche », « 2 tâches ». */
export function countTasks(count: number): string {
    return count <= 1 ? `${count} tâche` : `${count} tâches`
}
