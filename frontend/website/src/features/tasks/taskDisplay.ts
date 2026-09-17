import type { Task, TaskState } from '../../api/types'

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

/**
 * The list arrives sorted by category; this is what turns that order into visible groups without
 * re-sorting anything the API already decided.
 */
export function groupByCategory(tasks: Task[]): { label: string; tasks: Task[] }[] {
    const groups: { label: string; tasks: Task[] }[] = []

    for (const task of tasks) {
        const label = task.category?.label ?? 'Sans catégorie'
        const current = groups.at(-1)

        if (current !== undefined && current.label === label) {
            current.tasks.push(task)
        } else {
            groups.push({ label, tasks: [task] })
        }
    }

    return groups
}
