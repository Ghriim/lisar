import type { Task } from '../../api/types'

/**
 * What ticking a task off earns. The API does not say yet — the gains are still to be settled — so
 * this is the one place it will be read from, and until then the rows show « +— XP ».
 */
export function taskExperience(_task: Task): number | null {
    return null
}

/** « +30 XP », or « +— XP » while it is not known. */
export function formatExperience(experience: number | null): string {
    return `+${experience ?? '—'} XP`
}

/** The sum over several tasks, or null as soon as one is not known. */
export function totalExperience(tasks: Task[]): number | null {
    let total = 0
    for (const task of tasks) {
        const experience = taskExperience(task)
        if (experience === null) {
            return null
        }
        total += experience
    }

    return total
}
