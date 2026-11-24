import type { HabitPlanningEntry, TaskPlanningEntry, TrainingPlanningEntry } from '../../api/types'

/** The kinds of planning entry: one per domain, each kept apart from the others. */
export type PlanningKind = 'training' | 'task' | 'habit'

/** An entry, with the kind it is of. */
export type KindedEntry =
    | { kind: 'training'; entry: TrainingPlanningEntry }
    | { kind: 'task'; entry: TaskPlanningEntry }
    | { kind: 'habit'; entry: HabitPlanningEntry }

export const KINDS: { value: PlanningKind; label: string }[] = [
    { value: 'training', label: 'Entraînement' },
    { value: 'task', label: 'Tâche' },
    { value: 'habit', label: 'Habitude' },
]

/** What the entry plans: the routine's name, the task's title, or the habit's name. */
export function subjectOf(kinded: KindedEntry): string {
    switch (kinded.kind) {
        case 'training':
            return kinded.entry.routineName
        case 'task':
            return kinded.entry.title
        case 'habit':
            return kinded.entry.habitName
    }
}
