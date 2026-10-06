import { type FormEvent, useState } from 'react'
import type { WorkoutMovement, WorkoutSet } from '../../api/types'
import {
    Alert,
    Button,
    Field,
    FormActions,
    FormGrid,
    Select,
    TextInput,
    useViolations,
    type Option,
} from '../../components'
import { useAddWorkoutSet, useUpdateWorkoutSet, useWorkoutSetTypes } from './queries'
import { decimalField, durationField, failureOf, formatRpe, parseDecimal, parseDuration } from './workoutFormat'

interface SetFormProps {
    workoutId: number
    exerciseId: number
    movement: WorkoutMovement
    /** The set being corrected, or null to log a new one. */
    editing: WorkoutSet | null
    /**
     * The set the measures open on when logging a new one: the previous set of this movement, in
     * this workout or the last time. A load is a measurement, and the next one is almost always
     * the last one again — see docs/dev/frontend-conventions.md, §4.
     */
    previous: WorkoutSet | null
    onDone: () => void
}

/** 1 to 10, by halves. */
const RPE_OPTIONS: Option[] = Array.from({ length: 19 }, (_, index) => {
    const rpe = 1 + index / 2

    return { value: `${rpe}`, label: formatRpe(rpe) }
})

/**
 * One set: exactly the measures its movement tracks, then an RPE and a set type, both optional and
 * neither preselected — an untyped set is an ordinary working set.
 */
export function SetForm({ workoutId, exerciseId, movement, editing, previous, onDone }: SetFormProps) {
    const add = useAddWorkoutSet()
    const update = useUpdateWorkoutSet()
    const setTypes = useWorkoutSetTypes()

    const mutation = editing === null ? add : update
    const violations = useViolations(mutation.error)

    const source = editing ?? previous
    const [reps, setReps] = useState(source?.reps?.toString() ?? '')
    const [weight, setWeight] = useState(decimalField(source?.weightInKilograms ?? null))
    const [duration, setDuration] = useState(durationField(source?.durationInSeconds ?? null))
    const [distance, setDistance] = useState(source?.distanceInMetres?.toString() ?? '')
    const [rpe, setRpe] = useState(editing?.rpe?.toString() ?? '')
    const [setTypeId, setSetTypeId] = useState(editing?.setType?.id.toString() ?? '')

    const typeOptions: Option[] = (setTypes.data ?? []).map((type) => ({ value: `${type.id}`, label: type.name }))
    // A type retired since stays on the set it is on, through a correction.
    if (editing?.setType && !typeOptions.some((option) => option.value === `${editing.setType?.id}`)) {
        typeOptions.push({ value: `${editing.setType.id}`, label: editing.setType.name })
    }

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        // Only what the movement tracks is sent: anything else would be refused.
        const payload = {
            reps: movement.tracksReps ? parseDecimal(reps) : null,
            weightInKilograms: movement.tracksWeight ? parseDecimal(weight) : null,
            durationInSeconds: movement.tracksDuration ? parseDuration(duration) : null,
            distanceInMetres: movement.tracksDistance ? parseDecimal(distance) : null,
            rpe: rpe === '' ? null : Number(rpe),
            setTypeId: setTypeId === '' ? null : Number(setTypeId),
        }

        try {
            if (editing === null) {
                await add.mutateAsync({ id: workoutId, exerciseId, payload })
            } else {
                await update.mutateAsync({ id: workoutId, setId: editing.id, payload })
            }
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <FormGrid columns={2}>
                {movement.tracksReps && (
                    <Field label={movement.isUnilateral ? 'Répétitions / côté' : 'Répétitions'} errors={violations.for('reps')}>
                        <TextInput
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={reps}
                            autoFocus
                            onChange={(event) => setReps(event.target.value)}
                        />
                    </Field>
                )}

                {movement.tracksWeight && (
                    <Field label="Charge (kg)" errors={violations.for('weightInKilograms')}>
                        <TextInput
                            inputMode="decimal"
                            value={weight}
                            autoFocus={!movement.tracksReps}
                            onChange={(event) => setWeight(event.target.value)}
                        />
                    </Field>
                )}

                {movement.tracksDuration && (
                    <Field label="Durée (s ou m:ss)" errors={violations.for('durationInSeconds')}>
                        <TextInput
                            inputMode="numeric"
                            value={duration}
                            placeholder="1:30"
                            onChange={(event) => setDuration(event.target.value)}
                        />
                    </Field>
                )}

                {movement.tracksDistance && (
                    <Field label="Distance (m)" errors={violations.for('distanceInMetres')}>
                        <TextInput
                            type="number"
                            inputMode="numeric"
                            min={1}
                            value={distance}
                            onChange={(event) => setDistance(event.target.value)}
                        />
                    </Field>
                )}

                <Field label="RPE (facultatif)" errors={violations.for('rpe')}>
                    <Select value={rpe} onChange={setRpe} options={RPE_OPTIONS} placeholder="Aucun" />
                </Field>

                <Field label="Type (facultatif)" errors={violations.for('setTypeId')}>
                    <Select value={setTypeId} onChange={setSetTypeId} options={typeOptions} placeholder="Série normale" />
                </Field>
            </FormGrid>

            {violations.isGeneral && <Alert>{failureOf(mutation.error) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={mutation.isPending}>
                    {editing === null
                        ? mutation.isPending ? 'Ajouter…' : 'Ajouter'
                        : mutation.isPending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </form>
    )
}
