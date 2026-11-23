import { useNavigate } from 'react-router-dom'
import { failureOf } from '../workouts/workoutFormat'
import { useCopyRoutine } from './queries'

/** Duplicating a routine: the copy is made, and its page opens. */
export function useCopyRoutineAction() {
    const copy = useCopyRoutine()
    const navigate = useNavigate()

    return {
        isPending: copy.isPending,
        error: failureOf(copy.error),
        copy: (routineId: number) =>
            copy.mutate(routineId, { onSuccess: (routine) => void navigate(`/routines/${routine.id}`) }),
    }
}
