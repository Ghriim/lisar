import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { Alert, Loader, Stack, SystemPanel } from '../components'
import { useWorkout } from '../features/workouts/queries'
import { WorkoutDetailsForm } from '../features/workouts/WorkoutDetailsForm'
import { WorkoutStatsCard } from '../features/workouts/WorkoutStatsCard'

/**
 * Where a workout lands once finished: a word about it — its name, how it felt, a note — over
 * what it amounted to. Either way out goes to the workout's own page.
 */
export function WorkoutCompletePage() {
    const id = Number(useParams().id)
    const workout = useWorkout(id)
    const navigate = useNavigate()

    const done = () => void navigate(`/workouts/${id}`)

    if (workout.isPending) {
        return <Loader />
    }

    if (workout.isError) {
        return (
            <Alert>
                {workout.error instanceof ApiError && workout.error.status === 404
                    ? 'Cette séance n’existe pas.'
                    : 'Le System ne répond pas. Réessaie dans un instant.'}
            </Alert>
        )
    }

    // Nothing to close yet: the workout is still under way.
    if (workout.data.isInProgress) {
        return <Navigate to={`/workouts/${id}`} replace />
    }

    return (
        <Stack>
            <SystemPanel title="Séance terminée">
                <WorkoutDetailsForm workout={workout.data} onDone={done} />
            </SystemPanel>

            <WorkoutStatsCard workoutId={id} />
        </Stack>
    )
}
