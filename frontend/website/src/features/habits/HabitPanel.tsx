import { Check, RotateCcw, Settings2 } from 'lucide-react'
import { useState } from 'react'
import { EmptyState, IconButton, ListItem, Loader, Modal, SystemPanel } from '../../components'
import { HabitCatalog } from './HabitCatalog'
import { HabitStreak } from './HabitStreak'
import { useCompleteHabit, useHabits, useUncompleteHabit } from './queries'

/**
 * The kept habits, on the quest log: a line each, its last seven days beside it, and a tick for
 * the manual ones. A tracker-fed habit carries no tick — it is kept on its own when its tracker
 * crosses the mark. The catalogue to subscribe from is behind "Gérer".
 */
export function HabitPanel() {
    const habits = useHabits()
    const complete = useCompleteHabit()
    const uncomplete = useUncompleteHabit()
    const [managing, setManaging] = useState(false)

    const pending = complete.isPending || uncomplete.isPending

    return (
        <SystemPanel
            title="Habitudes"
            actions={<IconButton icon={Settings2} label="Gérer" onClick={() => setManaging(true)} />}
        >
            {habits.isPending && <Loader />}

            {habits.isSuccess && habits.data.length === 0 && (
                <EmptyState>Aucune habitude suivie. Ouvre « Gérer » pour en choisir.</EmptyState>
            )}

            {habits.isSuccess &&
                habits.data.map((habit) => (
                    <ListItem
                        key={habit.habitId}
                        title={habit.name}
                        meta={<HabitStreak days={habit.days} />}
                        actions={
                            habit.sourceKind === 'manual' ? (
                                habit.isCompletedToday ? (
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
                                )
                            ) : undefined
                        }
                    />
                ))}

            {managing && (
                <Modal title="Gérer les habitudes" onClose={() => setManaging(false)}>
                    <HabitCatalog />
                </Modal>
            )}
        </SystemPanel>
    )
}
