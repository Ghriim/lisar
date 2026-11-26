import { CalendarDays, RefreshCw, Settings2 } from 'lucide-react'
import { useState } from 'react'
import type { Habit } from '../../api/types'
import { Alert, CheckToggle, EmptyState, IconButton, Loader, Modal, SectionHeading, SystemPanel, Tooltip } from '../../components'
import { formatHabitRhythmShort } from '../planning/planningFormat'
import { useHabitPlanningEntries } from '../planning/queries'
import { HabitCatalog } from './HabitCatalog'
import { HabitCategoryIcon } from './HabitCategoryIcon'
import { HabitSchedule } from './HabitSchedule'
import { HabitWeek } from './HabitWeek'
import { habitStreak } from './habitStreak'
import { useCompleteHabit, useTodayHabits, useUncompleteHabit } from './queries'

const FAILURE = 'Le System ne répond pas. Réessaie dans un instant.'

/**
 * The kept habits today asks for, on the dashboard — which ones is the API's to say
 * (`/api/habits/today`): one flat list, a box each — its name with its category's
 * small icon, the days it counts and its run, its planning and its tick on the right, and its
 * last seven days under it, today last. A tracker-fed habit carries no tick: it is kept on its own when its
 * tracker crosses the mark. The catalogue to subscribe from is behind « Gérer les habitudes ».
 */
export function HabitPanel() {
    const habits = useTodayHabits()
    const [managing, setManaging] = useState(false)
    const [scheduling, setScheduling] = useState<Habit | null>(null)

    const planning = useHabitPlanningEntries()

    return (
        <section className="board-section" aria-labelledby="habits-title">
            <SectionHeading
                id="habits-title"
                title="Habitudes"
                actions={<IconButton icon={Settings2} label="Gérer les habitudes" large onClick={() => setManaging(true)} />}
            />

            <SystemPanel className="habit-card">
                {habits.isPending && <Loader />}
                {habits.isError && <Alert>{FAILURE}</Alert>}
                {habits.isSuccess && habits.data.length === 0 && <EmptyState>Aucune habitude prévue aujourd’hui.</EmptyState>}

                {habits.isSuccess &&
                    habits.data.map((habit) => (
                        <HabitLine
                            key={habit.habitId}
                            habit={habit}
                            rhythm={formatHabitRhythmShort(habit.habitId, planning.data ?? [])}
                            onSchedule={() => setScheduling(habit)}
                        />
                    ))}
            </SystemPanel>

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
        </section>
    )
}

interface HabitLineProps {
    habit: Habit
    rhythm: string | null
    onSchedule: () => void
}

function HabitLine({ habit, rhythm, onSchedule }: HabitLineProps) {
    const complete = useCompleteHabit()
    const uncomplete = useUncompleteHabit()
    const pending = complete.isPending || uncomplete.isPending

    const streak = habitStreak(habit)
    const details = [rhythm, `série ${streak ?? '—'}`].filter((part) => part !== null).join(' · ')

    return (
        <div className="habit-line">
            <div className="habit-line-head">
                <div className="habit-line-body">
                    <span className="habit-line-name">
                        <HabitCategoryIcon category={habit.category} />
                        {habit.name}
                    </span>
                    <span className="tracker-aux">{details}</span>
                </div>

                <div className="habit-line-actions">
                    <IconButton icon={CalendarDays} label="Planifier" subject={habit.name} medium onClick={onSchedule} />
                    {habit.sourceKind === 'manual' ? (
                        <CheckToggle
                            checked={habit.isCompletedToday}
                            label="Valider aujourd’hui"
                            subject={habit.name}
                            small
                            disabled={pending}
                            onToggle={() => (habit.isCompletedToday ? uncomplete.mutate(habit.habitId) : complete.mutate(habit.habitId))}
                        />
                    ) : (
                        <Tooltip label="Validée automatiquement">
                            <span className="habit-auto" role="img" tabIndex={0} aria-label={`${habit.name} : validée automatiquement`}>
                                <RefreshCw size={16} strokeWidth={1.6} aria-hidden />
                            </span>
                        </Tooltip>
                    )}
                </div>
            </div>

            <HabitWeek days={habit.days} />
        </div>
    )
}
