import { Check, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react'
import type { WorkoutExercise, WorkoutPreviousPerformance, WorkoutSet } from '../../api/types'
import { Chip, IconButton, IndexBadge, ListItem, Row } from '../../components'
import { setTypeShade } from './setTypeColours'
import { formatRpe, formatSetMeasures, formatShortDay } from './workoutFormat'

interface ExerciseCardProps {
    exercise: WorkoutExercise
    /** What this movement gave the last time it was done, if it was. */
    previous: WorkoutPreviousPerformance | undefined
    /** Offered only inside a superset: a lone movement goes with its block. */
    removable: boolean
    /**
     * Sets are ticked as done only while the workout runs: once finished, every set in it was
     * done, and there is nothing left to tick.
     */
    inProgress: boolean
    onToggleSet: (set: WorkoutSet) => void
    busy: boolean
    onAddSet: () => void
    onEditSet: (set: WorkoutSet) => void
    onDeleteSet: (set: WorkoutSet) => void
    onEditNote: () => void
    onDelete: () => void
}

/**
 * One movement of a workout: its note, what it gave the last time, and its sets in the order they
 * were logged. The sets are always unfolded — during a workout they are the point.
 */
export function ExerciseCard({
    exercise,
    previous,
    removable,
    inProgress,
    onToggleSet,
    busy,
    onAddSet,
    onEditSet,
    onDeleteSet,
    onEditNote,
    onDelete,
}: ExerciseCardProps) {
    const { movement } = exercise
    const setCount = exercise.sets.length
    // Green once every one of its sets is: the movement is over for this workout.
    const isDone = inProgress && setCount > 0 && exercise.sets.every((set) => set.isComplete)

    return (
        <ListItem
            title={movement.name}
            note={setCount === 0 ? undefined : `${setCount} série${setCount > 1 ? 's' : ''}`}
            description={exercise.note}
            defaultUnfolded
            done={isDone}
            meta={
                <>
                    {!movement.isActive && <Chip>Retiré</Chip>}
                    <LastTime previous={previous} isUnilateral={movement.isUnilateral} />
                </>
            }
            actions={
                <>
                    <IconButton icon={Plus} label="Ajouter une série" subject={movement.name} onClick={onAddSet} />
                    {/* The note is all there is to change about a movement once it is in. */}
                    <IconButton icon={Pencil} label="Modifier la note" subject={movement.name} onClick={onEditNote} />
                    {removable && (
                        <IconButton
                            icon={Trash2}
                            variant="danger"
                            label="Supprimer"
                            subject={movement.name}
                            disabled={busy}
                            onClick={onDelete}
                        />
                    )}
                </>
            }
        >
            {setCount > 0 &&
                exercise.sets.map((set, index) => (
                    <ListItem
                        key={set.id}
                        // The rank, framed in the type's colour: the measures are what the row says.
                        icon={
                            <IndexBadge
                                colour={setTypeShade(set.setType.colour)}
                                label={set.setType.name}
                                subject={`Série ${index + 1}`}
                            >
                                {index + 1}
                            </IndexBadge>
                        }
                        title={formatSetMeasures(set, movement)}
                        note={set.rpe === null ? undefined : formatRpe(set.rpe)}
                        // Done turns green, and stays readable: the next set is often the same again.
                        done={inProgress && set.isComplete}
                        actions={
                            <>
                                {inProgress &&
                                    (set.isComplete ? (
                                        <IconButton
                                            icon={RotateCcw}
                                            label="Décocher"
                                            subject={`${movement.name}, série ${index + 1}`}
                                            disabled={busy}
                                            onClick={() => onToggleSet(set)}
                                        />
                                    ) : (
                                        <IconButton
                                            icon={Check}
                                            label="Valider"
                                            subject={`${movement.name}, série ${index + 1}`}
                                            disabled={busy}
                                            onClick={() => onToggleSet(set)}
                                        />
                                    ))}
                                <IconButton
                                    icon={Pencil}
                                    label="Modifier"
                                    subject={`${movement.name}, série ${index + 1}`}
                                    onClick={() => onEditSet(set)}
                                />
                                {/* No question asked: a set is logged again in one gesture. */}
                                <IconButton
                                    icon={Trash2}
                                    variant="danger"
                                    label="Supprimer"
                                    subject={`${movement.name}, série ${index + 1}`}
                                    disabled={busy}
                                    onClick={() => onDeleteSet(set)}
                                />
                            </>
                        }
                    />
                ))}
        </ListItem>
    )
}

function LastTime({ previous, isUnilateral }: { previous: WorkoutPreviousPerformance | undefined; isUnilateral: boolean }) {
    if (previous === undefined) {
        return <span className="tracker-note">Jamais fait avant.</span>
    }

    return (
        <Row wrap style={{ gap: 6 }}>
            <span className="tracker-note">La dernière fois, le {formatShortDay(previous.startedAt)} :</span>
            {previous.sets.map((set) => (
                <Chip key={set.id}>{formatSetMeasures(set, { isUnilateral })}</Chip>
            ))}
        </Row>
    )
}
