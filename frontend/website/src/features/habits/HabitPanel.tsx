import { CalendarDays, Check, RotateCcw, Settings2 } from 'lucide-react'
import { useState } from 'react'
import type { Habit } from '../../api/types'
import { DataList, IconButton, ListItem, Modal, SystemPanel } from '../../components'
import { groupByCategoryIfAny } from '../categories/groupByCategory'
import { formatHabitRhythm } from '../planning/planningFormat'
import { useHabitPlanningEntries } from '../planning/queries'
import { HabitCatalog } from './HabitCatalog'
import { HabitSchedule } from './HabitSchedule'
import { HabitStreak } from './HabitStreak'
import { useCompleteHabit, useHabits, useUncompleteHabit } from './queries'

/**
 * The kept habits, on the dashboard: a line each, the days it counts beside its name, its last
 * seven days under it, and a tick for the manual ones. A tracker-fed habit carries no tick — it is
 * kept on its own when its tracker crosses the mark. Each line opens its own planning; the
 * catalogue to subscribe from is behind "Gérer".
 */
export function HabitPanel() {
    const habits = useHabits()
    const complete = useCompleteHabit()
    const uncomplete = useUncompleteHabit()
    const planning = useHabitPlanningEntries()
    const [managing, setManaging] = useState(false)
    const [scheduling, setScheduling] = useState<Habit | null>(null)

    const pending = complete.isPending || uncomplete.isPending

    // Under their category, like the tasks.
    const groups = groupByCategoryIfAny(habits.data ?? [], (habit) => habit.category)

    return (
        <SystemPanel
            title="Habitudes"
            actions={<IconButton icon={Settings2} label="Gérer" onClick={() => setManaging(true)} />}
        >
            <DataList<Habit>
                groups={groups}
                keyOf={(habit) => habit.habitId}
                loading={habits.isPending}
                error={habits.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                emptyText="Aucune habitude suivie. Ouvre « Gérer » pour en choisir."
                renderItem={(habit) => {
                    return (
                        <ListItem
                            title={habit.name}
                            description={formatHabitRhythm(habit.habitId, planning.data ?? [])}
                            footer={<HabitStreak days={habit.days} />}
                            actions={
                                <>
                                    <IconButton icon={CalendarDays} label="Planifier" subject={habit.name} onClick={() => setScheduling(habit)} />
                                    {habit.sourceKind === 'manual' &&
                                        (habit.isCompletedToday ? (
                                            <IconButton
                                                icon={RotateCcw}
                                                label="Décocher"
                                                subject={habit.name}
                                                disabled={pending}
                                                onClick={() => uncomplete.mutate(habit.habitId)}
                                            />
                                        ) : (
                                            <IconButton
                                                icon={Check}
                                                label="Valider"
                                                subject={habit.name}
                                                disabled={pending}
                                                onClick={() => complete.mutate(habit.habitId)}
                                            />
                                        ))}
                                </>
                            }
                        />
                    )
                }}
            />

            {scheduling !== null && (
                <Modal title={`Planifier · ${scheduling.name}`} onClose={() => setScheduling(null)}>
                    <HabitSchedule habitId={scheduling.habitId} habitName={scheduling.name} />
                </Modal>
            )}

            {managing && (
                <Modal title="Gérer les habitudes" onClose={() => setManaging(false)}>
                    <HabitCatalog />
                </Modal>
            )}
        </SystemPanel>
    )
}
