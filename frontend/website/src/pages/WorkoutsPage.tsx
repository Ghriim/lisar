import { Play } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Alert, IconButton, Loader, Stack } from '../components'
import { useCurrentWorkout } from '../features/workouts/queries'
import { WorkoutHistory } from '../features/workouts/WorkoutHistory'
import { workoutName } from '../features/workouts/workoutFormat'
import { WorkoutOverviewCard } from '../features/workouts/WorkoutOverviewCard'
import { WorkoutStarter } from '../features/workouts/WorkoutStarter'

/**
 * Where a workout is started and found again. There is never more than one in progress: when
 * there is one, the page shows its overview, with resuming it as the only action — everything
 * else, its exercises first, is on its own page.
 */
export function WorkoutsPage() {
    const current = useCurrentWorkout()
    const navigate = useNavigate()

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
                            label="Reprendre"
                            subject={workoutName(current.data)}
                            onClick={() => void navigate(`/workouts/${current.data?.id}`)}
                        />
                    }
                />
            )}

            <WorkoutHistory />
        </Stack>
    )
}
