import { Pencil, Plus, Timer, Trash2 } from 'lucide-react'
import type { RoutineExercise, RoutineSet, SetType } from '../../api/types'
import { Chip, IconButton, IndexBadge, ListItem } from '../../components'
import { setTypeShade } from '../workouts/setTypeColours'
import { formatDuration } from '../workouts/workoutFormat'
import { formatPrescription, formatTodayLoad } from './routineFormat'

interface RoutineExerciseCardProps {
    exercise: RoutineExercise
    /** The type every prescribed set is of for now; undefined until the list of types arrives. */
    defaultSetType: SetType | undefined
    /** Offered only inside a superset: a lone movement goes with its block. */
    removable: boolean
    busy: boolean
    onAddSet: () => void
    onEditSet: (set: RoutineSet) => void
    onDeleteSet: (set: RoutineSet) => void
    onEditDetails: () => void
    onDelete: () => void
}

/**
 * One movement of a routine: its note, its rest, and its sets — each with what it prescribes and,
 * dimmed, the load it would give a workout started now. Nothing is ticked: a routine is never done,
 * only started.
 */
export function RoutineExerciseCard({
    exercise,
    defaultSetType,
    removable,
    busy,
    onAddSet,
    onEditSet,
    onDeleteSet,
    onEditDetails,
    onDelete,
}: RoutineExerciseCardProps) {
    const { movement } = exercise
    const setCount = exercise.sets.length

    return (
        <ListItem
            title={movement.name}
            note={setCount === 0 ? undefined : `${setCount} série${setCount > 1 ? 's' : ''}`}
            description={exercise.note}
            defaultUnfolded
            meta={
                <>
                    {!movement.isActive && <Chip>Retiré</Chip>}
                    <Chip>
                        <Timer size={11} strokeWidth={2} aria-hidden />
                        Repos {formatDuration(exercise.restInSeconds ?? 0)}
                    </Chip>
                </>
            }
            actions={
                <>
                    <IconButton icon={Plus} label="Ajouter une série" subject={movement.name} onClick={onAddSet} />
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
                        // Always of the default type for now: the rank in its colour, as on a workout.
                        icon={
                            <IndexBadge
                                colour={setTypeShade(defaultSetType?.colour ?? 'grey')}
                                label={defaultSetType?.name ?? 'Série'}
                                subject={`Série ${index + 1}`}
                            >
                                {index + 1}
                            </IndexBadge>
                        }
                        title={formatPrescription(set, movement)}
                        note={formatTodayLoad(set)}
                        actions={
                            <>
                                <IconButton
                                    icon={Pencil}
                                    label="Modifier"
                                    subject={`${movement.name}, série ${index + 1}`}
                                    onClick={() => onEditSet(set)}
                                />
                                {/* No question asked: a set is prescribed again in one gesture. */}
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
