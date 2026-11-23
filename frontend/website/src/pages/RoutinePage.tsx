import { useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../api/client'
import { Alert, Button, Loader, Row, Stack } from '../components'
import { useRoutine } from '../features/routines/queries'
import { RoutineSheet } from '../features/routines/RoutineSheet'

/** One routine, opened to shape it or to start a workout from it. */
export function RoutinePage() {
    const id = Number(useParams().id)
    const routine = useRoutine(id)
    const navigate = useNavigate()

    // The list it was opened from: the « Routines » tab of /workouts.
    const back = () => void navigate('/workouts', { state: { tab: 'routines' } })

    return (
        <Stack>
            <Row>
                <Button variant="quiet" onClick={back}>
                    Revenir
                </Button>
            </Row>

            {routine.isPending ? (
                <Loader />
            ) : routine.isError ? (
                <Alert>
                    {routine.error instanceof ApiError && (routine.error.status === 404 || routine.error.status === 401)
                        ? 'Cette routine n’existe pas.'
                        : 'Le System ne répond pas. Réessaie dans un instant.'}
                </Alert>
            ) : (
                <RoutineSheet key={routine.data.id} routine={routine.data} onDeleted={back} />
            )}
        </Stack>
    )
}
