import { useLocation, useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { Alert, Button, Loader, Row, Stack } from '../components'
import { useWorkout } from '../features/workouts/queries'
import type { WorkoutCopyArrival } from '../features/workouts/useCopyWorkoutAction'
import { WorkoutSheet } from '../features/workouts/WorkoutSheet'

/** One workout of the history, opened to read it or correct it. */
export function WorkoutPage() {
    const id = Number(useParams().id)
    const workout = useWorkout(id)
    const navigate = useNavigate()
    const skipped = (useLocation().state as WorkoutCopyArrival | null)?.skippedMovements ?? []

    const back = () => void navigate('/workouts')

    return (
        <Stack>
            <Row>
                <Button variant="quiet" onClick={back}>
                    Revenir
                </Button>
            </Row>

            {workout.isPending ? (
                <Loader />
            ) : workout.isError ? (
                <Alert>
                    {workout.error instanceof ApiError && workout.error.status === 404
                        ? 'Cette séance n’existe pas.'
                        : 'Le System ne répond pas. Réessaie dans un instant.'}
                </Alert>
            ) : (
                <>
                    {skipped.length > 0 && (
                        <p className="tracker-note">
                            {skipped.length > 1 ? 'Laissés de côté, retirés depuis' : 'Laissé de côté, retiré depuis'} :{' '}
                            {skipped.join(', ')}.
                        </p>
                    )}
                    <WorkoutSheet workout={workout.data} onDeleted={back} />
                </>
            )}
        </Stack>
    )
}
