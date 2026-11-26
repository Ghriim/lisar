import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { Routine, RoutineBlock, RoutineExercise, RoutineSet, WorkoutMovementChoice } from '../../api/types'
import { Alert, ConfirmDialog, EmptyState, IconButton, Modal, Row, SystemPanel } from '../../components'
import { ExerciseDetailsForm } from '../workouts/ExerciseDetailsForm'
import { MovementPicker } from '../workouts/MovementPicker'
import { useWorkoutSetTypes } from '../workouts/queries'
import { failureOf } from '../workouts/workoutFormat'
import {
    useAddRoutineBlock,
    useAddRoutineExercise,
    useDeleteRoutineBlock,
    useDeleteRoutineExercise,
    useDeleteRoutineSet,
    useReorderRoutineBlocks,
    useUpdateRoutineExercise,
} from './queries'
import { RoutineExerciseCard } from './RoutineExerciseCard'
import { RoutineSetForm } from './RoutineSetForm'

/** A superset takes six movements at most — the API's bound, said here so the picker stops there. */
const MAX_MOVEMENTS_PER_BLOCK = 6

type Panel =
    | { kind: 'add-block' }
    | { kind: 'add-exercise'; block: RoutineBlock }
    | { kind: 'details'; exercise: RoutineExercise }
    | { kind: 'set'; exercise: RoutineExercise; editing: RoutineSet | null }
    | { kind: 'delete-block'; block: RoutineBlock }
    | { kind: 'delete-exercise'; exercise: RoutineExercise }
    | null

/**
 * A routine prescribes reps, with or without a load: a movement timed or measured over a distance
 * waits for the targets that would prescribe it. The picker offers only the others.
 */
function isPrescribable(movement: WorkoutMovementChoice): boolean {
    return movement.tracksReps && !movement.tracksDuration && !movement.tracksDistance
}

/**
 * What a routine is made of: its blocks in order, their movements and the sets each prescribes,
 * with the same gestures as on a workout — but nothing to tick.
 */
export function RoutineExercisesCard({ routine }: { routine: Routine }) {
    const [panel, setPanel] = useState<Panel>(null)
    const close = () => setPanel(null)

    const setTypes = useWorkoutSetTypes()
    const defaultSetType = setTypes.data?.find((type) => type.isDefaultType)

    const addBlock = useAddRoutineBlock()
    const addExercise = useAddRoutineExercise()
    const updateExercise = useUpdateRoutineExercise()
    const reorder = useReorderRoutineBlocks()
    const removeBlock = useDeleteRoutineBlock()
    const removeExercise = useDeleteRoutineExercise()
    const removeSet = useDeleteRoutineSet()

    const actions = [reorder, removeBlock, removeExercise, removeSet]
    const busy = actions.some((one) => one.isPending)
    const error = failureOf(actions.find((one) => one.error !== null)?.error)

    const move = (index: number, offset: -1 | 1) => {
        const ids = routine.blocks.map((block) => block.id)
        ;[ids[index], ids[index + offset]] = [ids[index + offset], ids[index]]
        reorder.mutate({ id: routine.id, blockIds: ids })
    }

    return (
        <>
            <SystemPanel
                title="Exercices"
                actions={<IconButton icon={Plus} label="Ajouter un exercice" variant="primary" large onClick={() => setPanel({ kind: 'add-block' })} />}
            >
                {routine.blocks.length === 0 ? (
                    <EmptyState>Aucun exercice pour l’instant.</EmptyState>
                ) : (
                    routine.blocks.map((block, index) => {
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
                                            disabled={busy || index === routine.blocks.length - 1}
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
                                                    ? removeBlock.mutate({ id: routine.id, blockId: block.id })
                                                    : setPanel({ kind: 'delete-block', block })
                                            }
                                        />
                                    </Row>
                                </div>

                                {block.exercises.map((exercise) => (
                                    <RoutineExerciseCard
                                        key={exercise.id}
                                        exercise={exercise}
                                        defaultSetType={defaultSetType}
                                        removable={isSuperset}
                                        busy={busy}
                                        onAddSet={() => setPanel({ kind: 'set', exercise, editing: null })}
                                        onEditSet={(set) => setPanel({ kind: 'set', exercise, editing: set })}
                                        onDeleteSet={(set) => removeSet.mutate({ id: routine.id, setId: set.id })}
                                        onEditDetails={() => setPanel({ kind: 'details', exercise })}
                                        onDelete={() =>
                                            exercise.sets.length === 0
                                                ? removeExercise.mutate({ id: routine.id, exerciseId: exercise.id })
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
                <Modal title="Ajouter un exercice" tall onClose={close}>
                    <MovementPicker
                        max={MAX_MOVEMENTS_PER_BLOCK}
                        accepts={isPrescribable}
                        pending={addBlock.isPending}
                        failure={addBlock.error}
                        restFieldOf={(index) => `exercises[${index}].restInSeconds`}
                        onCancel={close}
                        onSubmit={(exercises) => addBlock.mutate({ id: routine.id, exercises }, { onSuccess: close })}
                    />
                </Modal>
            )}

            {panel?.kind === 'add-exercise' && (
                <Modal title="Ajouter au bloc" tall onClose={close}>
                    <MovementPicker
                        max={1}
                        accepts={isPrescribable}
                        pending={addExercise.isPending}
                        failure={addExercise.error}
                        restFieldOf={() => 'restInSeconds'}
                        onCancel={close}
                        onSubmit={([exercise]) =>
                            addExercise.mutate({ id: routine.id, blockId: panel.block.id, exercise }, { onSuccess: close })
                        }
                    />
                </Modal>
            )}

            {panel?.kind === 'details' && (
                <Modal title={`Modifier le mouvement · ${panel.exercise.movement.name}`} onClose={close}>
                    <ExerciseDetailsForm
                        exercise={panel.exercise}
                        save={(details) => updateExercise.mutateAsync({ id: routine.id, exerciseId: panel.exercise.id, ...details })}
                        pending={updateExercise.isPending}
                        failure={updateExercise.error}
                        onDone={close}
                    />
                </Modal>
            )}

            {panel?.kind === 'set' && (
                <Modal
                    title={`${panel.editing === null ? 'Nouvelle série' : 'Modifier la série'} · ${panel.exercise.movement.name}`}
                    onClose={close}
                >
                    <RoutineSetForm
                        routineId={routine.id}
                        exerciseId={panel.exercise.id}
                        movement={panel.exercise.movement}
                        editing={panel.editing}
                        previous={panel.exercise.sets.at(-1) ?? null}
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
                        removeBlock.mutate({ id: routine.id, blockId: target.id })
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
                        removeExercise.mutate({ id: routine.id, exerciseId: target.id })
                    }}
                />
            )}
        </>
    )
}

function setCountOf(exercises: RoutineExercise[]): number {
    return exercises.reduce((total, exercise) => total + exercise.sets.length, 0)
}

function seriesPhrase(count: number): string {
    return count === 1 ? 'sa série sera supprimée.' : `ses ${count} séries seront supprimées.`
}
