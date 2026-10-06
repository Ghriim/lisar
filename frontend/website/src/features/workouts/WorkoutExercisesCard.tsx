import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { Workout, WorkoutBlock, WorkoutExercise, WorkoutSet } from '../../api/types'
import { Alert, ConfirmDialog, EmptyState, IconButton, Modal, Row, SystemPanel } from '../../components'
import { ExerciseCard } from './ExerciseCard'
import { ExerciseNoteForm } from './ExerciseNoteForm'
import { MovementPicker } from './MovementPicker'
import {
    useAddWorkoutBlock,
    useAddWorkoutExercise,
    useCompleteWorkoutSet,
    useDeleteWorkoutBlock,
    useDeleteWorkoutExercise,
    useDeleteWorkoutSet,
    usePreviousPerformances,
    useReorderWorkoutBlocks,
    useUncompleteWorkoutSet,
} from './queries'
import { SetForm } from './SetForm'
import { failureOf } from './workoutFormat'

/** A superset takes six movements at most — the API's bound, said here so the picker stops there. */
const MAX_MOVEMENTS_PER_BLOCK = 6

/** Which window is open over the card. Closed means there is none. */
type Panel =
    | { kind: 'add-block' }
    | { kind: 'add-exercise'; block: WorkoutBlock }
    | { kind: 'note'; exercise: WorkoutExercise }
    | { kind: 'set'; exercise: WorkoutExercise; editing: WorkoutSet | null; previous: WorkoutSet | null }
    | { kind: 'delete-block'; block: WorkoutBlock }
    | { kind: 'delete-exercise'; exercise: WorkoutExercise }
    | null

/**
 * What a workout is made of: its blocks in order, their movements, the sets of each and what each
 * gave the last time — with everything that adds to, corrects or reorders them. The same card logs
 * the workout in progress and corrects a finished one; only ticking sets is kept for the first.
 */
export function WorkoutExercisesCard({ workout }: { workout: Workout }) {
    const [panel, setPanel] = useState<Panel>(null)
    const close = () => setPanel(null)

    const previous = usePreviousPerformances(workout.id)
    const lastTimes = new Map((previous.data ?? []).map((performance) => [performance.movementId, performance]))

    const addBlock = useAddWorkoutBlock()
    const addExercise = useAddWorkoutExercise()
    const reorder = useReorderWorkoutBlocks()
    const removeBlock = useDeleteWorkoutBlock()
    const removeExercise = useDeleteWorkoutExercise()
    const removeSet = useDeleteWorkoutSet()
    const completeSet = useCompleteWorkoutSet()
    const uncompleteSet = useUncompleteWorkoutSet()

    const actions = [reorder, removeBlock, removeExercise, removeSet, completeSet, uncompleteSet]
    const busy = actions.some((one) => one.isPending)
    const error = failureOf(actions.find((one) => one.error !== null)?.error)

    const move = (index: number, offset: -1 | 1) => {
        const ids = workout.blocks.map((block) => block.id)
        ;[ids[index], ids[index + offset]] = [ids[index + offset], ids[index]]
        reorder.mutate({ id: workout.id, blockIds: ids })
    }

    /** Logging a set opens on the one before it: in this workout, or else the last time. */
    const openNewSet = (exercise: WorkoutExercise) => {
        const before = exercise.sets.at(-1) ?? lastTimes.get(exercise.movement.id)?.sets[0] ?? null
        setPanel({ kind: 'set', exercise, editing: null, previous: before })
    }

    return (
        <>
            <SystemPanel
                title="Exercices"
                actions={
                    <IconButton icon={Plus} label="Ajouter un exercice" onClick={() => setPanel({ kind: 'add-block' })} />
                }
            >
                {workout.blocks.length === 0 ? (
                    <EmptyState>Aucun exercice pour l’instant.</EmptyState>
                ) : (
                    workout.blocks.map((block, index) => {
                        const isSuperset = block.exercises.length > 1
                        const label = `Bloc ${index + 1}${isSuperset ? ' · superset' : ''}`

                        return (
                            <section key={block.id} className="list-group" aria-label={label}>
                                <div className="workout-block-head">
                                    <h2 className="list-group-heading">{label}</h2>
                                    <Row style={{ gap: 4 }}>
                                        <IconButton
                                            icon={ArrowUp}
                                            small
                                            label="Monter"
                                            subject={label}
                                            disabled={busy || index === 0}
                                            onClick={() => move(index, -1)}
                                        />
                                        <IconButton
                                            icon={ArrowDown}
                                            small
                                            label="Descendre"
                                            subject={label}
                                            disabled={busy || index === workout.blocks.length - 1}
                                            onClick={() => move(index, 1)}
                                        />
                                        {block.exercises.length < MAX_MOVEMENTS_PER_BLOCK && (
                                            <IconButton
                                                icon={Plus}
                                                small
                                                label="Ajouter au bloc"
                                                subject={label}
                                                onClick={() => setPanel({ kind: 'add-exercise', block })}
                                            />
                                        )}
                                        <IconButton
                                            icon={Trash2}
                                            small
                                            variant="danger"
                                            label="Supprimer le bloc"
                                            subject={label}
                                            disabled={busy}
                                            onClick={() =>
                                                setCountOf(block.exercises) === 0
                                                    ? removeBlock.mutate({ id: workout.id, blockId: block.id })
                                                    : setPanel({ kind: 'delete-block', block })
                                            }
                                        />
                                    </Row>
                                </div>

                                {block.exercises.map((exercise) => (
                                    <ExerciseCard
                                        key={exercise.id}
                                        exercise={exercise}
                                        previous={lastTimes.get(exercise.movement.id)}
                                        removable={isSuperset}
                                        inProgress={workout.isInProgress}
                                        onToggleSet={(set) =>
                                            (set.isComplete ? uncompleteSet : completeSet).mutate({ id: workout.id, setId: set.id })
                                        }
                                        busy={busy}
                                        onAddSet={() => openNewSet(exercise)}
                                        onEditSet={(set) => setPanel({ kind: 'set', exercise, editing: set, previous: null })}
                                        onDeleteSet={(set) => removeSet.mutate({ id: workout.id, setId: set.id })}
                                        onEditNote={() => setPanel({ kind: 'note', exercise })}
                                        onDelete={() =>
                                            exercise.sets.length === 0
                                                ? removeExercise.mutate({ id: workout.id, exerciseId: exercise.id })
                                                : setPanel({ kind: 'delete-exercise', exercise })
                                        }
                                    />
                                ))}
                            </section>
                        )
                    })
                )}
                {error !== null && <Alert>{error}</Alert>}
            </SystemPanel>
            {panel?.kind === 'add-block' && (
                <Modal title="Ajouter un exercice" onClose={close}>
                    <MovementPicker
                        max={MAX_MOVEMENTS_PER_BLOCK}
                        pending={addBlock.isPending}
                        failure={addBlock.error}
                        onCancel={close}
                        onSubmit={(movementIds) => addBlock.mutate({ id: workout.id, movementIds }, { onSuccess: close })}
                    />
                </Modal>
            )}

            {panel?.kind === 'add-exercise' && (
                <Modal title="Ajouter au bloc" onClose={close}>
                    <MovementPicker
                        max={1}
                        pending={addExercise.isPending}
                        failure={addExercise.error}
                        onCancel={close}
                        onSubmit={([movementId]) =>
                            addExercise.mutate({ id: workout.id, blockId: panel.block.id, movementId }, { onSuccess: close })
                        }
                    />
                </Modal>
            )}

            {panel?.kind === 'note' && (
                <Modal title={`Note · ${panel.exercise.movement.name}`} onClose={close}>
                    <ExerciseNoteForm workoutId={workout.id} exercise={panel.exercise} onDone={close} />
                </Modal>
            )}

            {panel?.kind === 'set' && (
                <Modal
                    title={`${panel.editing === null ? 'Nouvelle série' : 'Modifier la série'} · ${panel.exercise.movement.name}`}
                    onClose={close}
                >
                    <SetForm
                        workoutId={workout.id}
                        exerciseId={panel.exercise.id}
                        movement={panel.exercise.movement}
                        editing={panel.editing}
                        previous={panel.previous}
                        onDone={close}
                    />
                </Modal>
            )}

            {panel?.kind === 'delete-block' && (
                <ConfirmDialog
                    title="Supprimer le bloc"
                    question={`${panel.block.exercises.map((exercise) => exercise.movement.name).join(', ')} : ${seriesPhrase(setCountOf(panel.block.exercises))} C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={close}
                    onConfirm={() => {
                        const target = panel.block
                        close()
                        removeBlock.mutate({ id: workout.id, blockId: target.id })
                    }}
                />
            )}

            {panel?.kind === 'delete-exercise' && (
                <ConfirmDialog
                    title="Supprimer le mouvement"
                    question={`${panel.exercise.movement.name} : ${seriesPhrase(panel.exercise.sets.length)} C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={close}
                    onConfirm={() => {
                        const target = panel.exercise
                        close()
                        removeExercise.mutate({ id: workout.id, exerciseId: target.id })
                    }}
                />
            )}
        </>
    )
}

function setCountOf(exercises: WorkoutExercise[]): number {
    return exercises.reduce((total, exercise) => total + exercise.sets.length, 0)
}

function seriesPhrase(count: number): string {
    return count === 1 ? 'sa série sera supprimée.' : `ses ${count} séries seront supprimées.`
}
