import { useState } from 'react'
import type { WorkoutMovement, WorkoutSet } from '../../api/types'
import {
    Alert,
    Button,
    DecimalField,
    DurationField,
    Form,
    FormActions,
    FormGrid,
    NumberField,
    type Option,
    SelectField,
    useViolations,
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
 * neither preselected. The empty type is the default one, which the server applies: it is the
 * placeholder rather than an option, so it is never chosen on anyone's behalf.
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
    const [setTypeId, setSetTypeId] = useState(
        editing === null || editing.setType.isDefaultType ? '' : editing.setType.id.toString(),
    )

    const defaultType = setTypes.data?.find((type) => type.isDefaultType)
    const typeOptions: Option[] = (setTypes.data ?? [])
        .filter((type) => !type.isDefaultType)
        .map((type) => ({ value: `${type.id}`, label: type.name }))
    // A type retired since stays on the set it is on, through a correction.
    if (editing !== null && setTypeId !== '' && !typeOptions.some((option) => option.value === setTypeId)) {
        typeOptions.push({ value: `${editing.setType.id}`, label: editing.setType.name })
    }

    const submit = async () => {
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
        <Form onSubmit={submit}>
            <FormGrid columns={2}>
                {movement.tracksReps && (
                    <NumberField
                        label={movement.isUnilateral ? 'Répétitions / côté' : 'Répétitions'}
                        isMandatory
                        errors={violations.for('reps')}
                        min={1}
                        value={reps}
                        autoFocus
                        onChange={setReps}
                    />
                )}

                {/* Not marked: a load is required only once the workout is over, which this form cannot see. */}
                {movement.tracksWeight && (
                    <DecimalField
                        label="Charge (kg)"
                        errors={violations.for('weightInKilograms')}
                        value={weight}
                        autoFocus={!movement.tracksReps}
                        onChange={setWeight}
                    />
                )}

                {movement.tracksDuration && (
                    <DurationField
                        label="Durée (s ou m:ss)"
                        isMandatory
                        errors={violations.for('durationInSeconds')}
                        value={duration}
                        placeholder="1:30"
                        onChange={setDuration}
                    />
                )}

                {movement.tracksDistance && (
                    <NumberField
                        label="Distance (m)"
                        isMandatory
                        errors={violations.for('distanceInMetres')}
                        min={1}
                        value={distance}
                        onChange={setDistance}
                    />
                )}

                <SelectField label="RPE" errors={violations.for('rpe')} value={rpe} onChange={setRpe} options={RPE_OPTIONS} placeholder="Aucun" />

                <SelectField
                    label="Type"
                    errors={violations.for('setTypeId')}
                    value={setTypeId}
                    onChange={setSetTypeId}
                    options={typeOptions}
                    placeholder={defaultType?.name ?? 'Par défaut'}
                />
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
        </Form>
    )
}
