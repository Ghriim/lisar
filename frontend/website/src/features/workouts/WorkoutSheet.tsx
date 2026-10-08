import { Check, Pencil, Repeat, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { Workout } from '../../api/types'
import { Button, ConfirmDialog, IconButton, Modal, Row, Stack } from '../../components'
import { useDeleteWorkout, useFinishWorkout } from './queries'
import { RestTimerBar } from './RestTimerBar'
import { useCopyWorkoutAction } from './useCopyWorkoutAction'
import { useRestTimer } from './useRestTimer'
import { WorkoutDetailsForm } from './WorkoutDetailsForm'
import { WorkoutExercisesCard } from './WorkoutExercisesCard'
import { failureOf, workoutName } from './workoutFormat'
import { WorkoutOverviewCard } from './WorkoutOverviewCard'
import { WorkoutStatsCard } from './WorkoutStatsCard'

interface WorkoutSheetProps {
    workout: Workout
    /** Called once the workout itself is gone, so whoever shows it can move on. */
    onDeleted?: () => void
}

/**
 * One workout on its own page: the one in progress as it is logged, or a past one to read and
 * correct. Its overview with what can be done to it, its bilan once finished, then its exercises.
 * Finishing it is a button of its own under the exercises — where the last set was just ticked —
 * and goes to the page that closes it.
 */
export function WorkoutSheet({ workout, onDeleted }: WorkoutSheetProps) {
    const [panel, setPanel] = useState<'details' | 'delete' | null>(null)
    const close = () => setPanel(null)
    const navigate = useNavigate()

    const finish = useFinishWorkout()
    const remove = useDeleteWorkout()
    const copy = useCopyWorkoutAction()
    const restTimer = useRestTimer()

    const busy = finish.isPending || remove.isPending || copy.isPending
    const error = failureOf(finish.error ?? remove.error) ?? copy.error
    const name = workoutName(workout)
    // The API finishes only a workout with sets, every one of them ticked: offered once it would.
    const sets = workout.blocks.flatMap((block) => block.exercises.flatMap((exercise) => exercise.sets))
    const isFinishable = sets.length > 0 && sets.every((set) => set.isComplete)

    return (
        <Stack>
            <WorkoutOverviewCard
                workout={workout}
                error={error}
                actions={
                    <Row style={{ gap: 6 }}>
                        <IconButton icon={Pencil} label="Modifier" subject={name} onClick={() => setPanel('details')} />
                        {!workout.isInProgress && copy.isOffered && (
                            <IconButton
                                icon={Repeat}
                                label="Refaire"
                                subject={name}
                                disabled={busy}
                                onClick={() => copy.copy(workout.id)}
                            />
                        )}
                        <IconButton
                            icon={Trash2}
                            variant="danger"
                            label={workout.isInProgress ? 'Abandonner' : 'Supprimer'}
                            subject={name}
                            disabled={busy}
                            onClick={() => setPanel('delete')}
                        />
                    </Row>
                }
            />

            {!workout.isInProgress && <WorkoutStatsCard workoutId={workout.id} />}

            <WorkoutExercisesCard workout={workout} restTimer={restTimer} />

            {workout.isInProgress && (
                <Row style={{ justifyContent: 'center' }}>
                    <Button
                        icon={Check}
                        disabled={busy || !isFinishable}
                        onClick={() =>
                            finish.mutate(workout.id, {
                                onSuccess: () => void navigate(`/workouts/${workout.id}/complete`),
                            })
                        }
                    >
                        {finish.isPending ? 'Terminer…' : 'Terminer'}
                    </Button>
                </Row>
            )}

            {workout.isInProgress && <RestTimerBar timer={restTimer} />}

            {panel === 'details' && (
                <Modal title="Modifier la séance" onClose={close}>
                    <WorkoutDetailsForm workout={workout} onDone={close} />
                </Modal>
            )}

            {panel === 'delete' && (
                <ConfirmDialog
                    title={workout.isInProgress ? 'Abandonner la séance' : 'Supprimer la séance'}
                    question={`« ${name} » et tout ce qui y est noté seront supprimés. C’est définitif.`}
                    confirmLabel={workout.isInProgress ? 'Abandonner' : 'Supprimer'}
                    onCancel={close}
                    onConfirm={() => {
                        close()
                        remove.mutate(workout, { onSuccess: onDeleted })
                    }}
                />
            )}
        </Stack>
    )
}
