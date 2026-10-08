import { Check, Pencil, Plus, RotateCcw, Timer, Trash2, Trophy } from 'lucide-react'
import type { ReactNode } from 'react'
import type { WorkoutExercise, WorkoutPreviousPerformance, WorkoutSet } from '../../api/types'
import { Chip, IconButton, IndexBadge, LinkChip, ListItem } from '../../components'
import { personalBestLabel, personalBestPath } from '../records/personalBestFormat'
import { setTypeShade } from './setTypeColours'
import { formatDuration, formatRpe, formatSetMeasures } from './workoutFormat'

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
    onEditDetails: () => void
    onDelete: () => void
}

/**
 * One movement of a workout: its note, its rest, and its sets in the order they were logged —
 * each beside the set of the same rank the last time, the one it is measured against. The sets are
 * always unfolded — during a workout they are the point.
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
    onEditDetails,
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
                    {/* Always there, « 0 s » without one: every movement reads the same way. */}
                    <Chip>
                        <Timer size={11} strokeWidth={2} aria-hidden />
                        Repos {formatDuration(exercise.restInSeconds ?? 0)}
                    </Chip>
                </>
            }
            actions={
                <>
                    <IconButton icon={Plus} label="Ajouter une série" subject={movement.name} onClick={onAddSet} />
                    {/* Its rest and its note are all there is to change about a movement once it is in. */}
                    <IconButton icon={Pencil} label="Modifier" subject={movement.name} onClick={onEditDetails} />
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
                        meta={setMeta(set, previous?.sets[index], movement.isUnilateral, inProgress)}
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

/**
 * Under a set: the set of the same rank the last time, when there was one, then the records it
 * beat, by name alone, in gold. A record shows the moment the set is ticked: the answer carries it.
 * Once the workout is finished each one opens the records page on it; while it runs the record may
 * still move with the next tick, so it leads nowhere yet. Nothing of either, no line at all.
 */
function setMeta(set: WorkoutSet, before: WorkoutSet | undefined, isUnilateral: boolean, inProgress: boolean): ReactNode {
    if (before === undefined && set.personalBests.length === 0) {
        return undefined
    }

    return (
        <>
            {before !== undefined && (
                <span className="set-last-time">Dernière fois : {formatSetMeasures(before, { isUnilateral })}</span>
            )}
            {set.personalBests.map((record) => {
                const content = (
                    <>
                        <Trophy size={11} strokeWidth={2} aria-hidden />
                        {personalBestLabel(record)}
                    </>
                )

                return inProgress ? (
                    <Chip key={record.id} colour="var(--warning)">
                        {content}
                    </Chip>
                ) : (
                    <LinkChip key={record.id} colour="var(--warning)" to={personalBestPath(record)}>
                        {content}
                    </LinkChip>
                )
            })}
        </>
    )
}
