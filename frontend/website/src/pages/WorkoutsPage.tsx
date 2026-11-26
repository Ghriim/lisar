import { Play, Plus } from 'lucide-react'
import { useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { Alert, IconButton, Loader, Row, Stack, SystemPanel, Tabs } from '../components'
import { RoutineCreator } from '../features/routines/RoutineCreator'
import { RoutineList } from '../features/routines/RoutineList'
import { useCurrentWorkout } from '../features/workouts/queries'
import { WorkoutHistory } from '../features/workouts/WorkoutHistory'
import { workoutName } from '../features/workouts/workoutFormat'
import { WorkoutOverviewCard } from '../features/workouts/WorkoutOverviewCard'
import { WorkoutStarter } from '../features/workouts/WorkoutStarter'

type View = 'history' | 'routines'

/** What a page coming back here may ask for: the tab it was opened from. */
export interface WorkoutsArrival {
    tab?: View
}

/**
 * Where a workout is started and found again. There is never more than one in progress: when
 * there is one, the page shows its overview, with resuming it as the only action — everything
 * else, its exercises first, is on its own page. Under it, one card with two tabs: the history, and
 * the routines a workout is started from in one gesture.
 */
export function WorkoutsPage() {
    const current = useCurrentWorkout()
    const navigate = useNavigate()
    const arrival = useLocation().state as WorkoutsArrival | null
    const [view, setView] = useState<View>(arrival?.tab ?? 'history')
    const [creating, setCreating] = useState(false)

    return (
        <Stack>
            {current.isPending ? (
                <Loader />
            ) : current.isError ? (
                <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
            ) : current.data === null ? (
                <WorkoutStarter />
            ) : (
                <WorkoutOverviewCard
                    workout={current.data}
                    actions={
                        <IconButton
                            icon={Play}
                            variant="primary"
                            large
                            label="Reprendre"
                            subject={workoutName(current.data)}
                            onClick={() => void navigate(`/workouts/${current.data?.id}`)}
                        />
                    }
                />
            )}

            <SystemPanel>
                <Tabs<View>
                    current={view}
                    onChange={setView}
                    tabs={[
                        { value: 'history', label: 'Historique' },
                        { value: 'routines', label: 'Routines' },
                    ]}
                />

                {view === 'history' ? (
                    <WorkoutHistory />
                ) : (
                    <Stack>
                        {/* Under the tabs rather than in the card's corner: it belongs to this tab, not to the card. */}
                        <Row end>
                            <IconButton icon={Plus} label="Créer" onClick={() => setCreating(true)} />
                        </Row>
                        <RoutineList />
                    </Stack>
                )}
            </SystemPanel>

            {creating && <RoutineCreator onClose={() => setCreating(false)} />}
        </Stack>
    )
}
