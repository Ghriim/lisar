import { useEffect, useState, type ReactNode } from 'react'
import type { Workout } from '../../api/types'
import { Alert, DefinitionList, Row, SystemPanel } from '../../components'
import { feelingFor } from './workoutFeelings'
import { formatElapsed, formatHour, formatLongDay, workoutName } from './workoutFormat'

interface WorkoutOverviewCardProps {
    workout: Workout
    /**
     * What can be done from where the card is shown: on the list, resuming the workout; on its own
     * page, editing, finishing, deleting. The card itself acts on nothing.
     */
    actions?: ReactNode
    /** What the last of those actions answered, when it was refused. */
    error?: string | null
}

/**
 * A workout at a glance: its name, its two moments and how long it lasted — ticking while it runs —
 * how it felt, and its note. Every field is listed, filled in or not.
 */
export function WorkoutOverviewCard({ workout, actions, error = null }: WorkoutOverviewCardProps) {
    const now = useNow(workout.isInProgress)
    const feeling = workout.feeling === null ? undefined : feelingFor(workout.feeling)

    return (
        <SystemPanel title={workoutName(workout)} actions={actions}>
            <DefinitionList
                items={[
                    {
                        label: 'Début',
                        placeholder: '—',
                        width: 'half',
                        value: `${formatLongDay(workout.startedAt)} à ${formatHour(workout.startedAt)}`,
                    },
                    {
                        label: 'Fin',
                        placeholder: 'En cours',
                        width: 'quarter',
                        value: workout.finishedAt === null ? null : formatHour(workout.finishedAt),
                    },
                    {
                        label: 'Durée',
                        placeholder: '—',
                        width: 'quarter',
                        value: formatElapsed(workout.startedAt, workout.finishedAt ?? now),
                    },
                    {
                        label: 'Ressenti',
                        placeholder: 'Aucun ressenti',
                        value:
                            feeling === undefined ? null : (
                                <Row style={{ gap: 8 }}>
                                    <feeling.icon size={18} aria-hidden />
                                    {feeling.label}
                                </Row>
                            ),
                    },
                    { label: 'Note', placeholder: 'Aucune note', value: workout.note },
                ]}
            />

            {error !== null && <Alert>{error}</Alert>}
        </SystemPanel>
    )
}

/** The time now, ticking every half minute while a workout runs, so its duration keeps up. */
function useNow(ticking: boolean): Date {
    const [now, setNow] = useState(() => new Date())

    useEffect(() => {
        if (!ticking) {
            return undefined
        }

        const timer = setInterval(() => setNow(new Date()), 30 * 1000)

        return () => clearInterval(timer)
    }, [ticking])

    return now
}
