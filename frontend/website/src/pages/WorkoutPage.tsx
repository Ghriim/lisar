import { useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { Alert, Button, Loader, Row, Stack } from '../components'
import { useWorkout } from '../features/workouts/queries'
import { WorkoutSheet } from '../features/workouts/WorkoutSheet'

/** One workout of the history, opened to read it or correct it. */
export function WorkoutPage() {
    const id = Number(useParams().id)
    const workout = useWorkout(id)
    const navigate = useNavigate()

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
                <WorkoutSheet workout={workout.data} onDeleted={back} />
            )}
        </Stack>
    )
}
