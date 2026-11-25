import { useState } from 'react'
import type { RoutineSet, WorkoutMovement } from '../../api/types'
import { Alert, Button, DecimalField, Form, FormActions, FormGrid, NumberField, useViolations } from '../../components'
import { decimalField, failureOf, parseDecimal } from '../workouts/workoutFormat'
import { useAddRoutineSet, useUpdateRoutineSet } from './queries'

interface RoutineSetFormProps {
    routineId: number
    exerciseId: number
    movement: WorkoutMovement
    /** The set being corrected, or null to prescribe a new one. */
    editing: RoutineSet | null
    /**
     * The set a new one opens on: the movement's last set. Three identical sets are the common
     * case, and each is written out on its own — opening on the one before saves retyping it.
     */
    previous: RoutineSet | null
    onDone: () => void
}

/**
 * One prescribed set: its reps, and for a movement with a load, its share of the estimated 1RM —
 * the load itself is worked out when a workout starts.
 */
export function RoutineSetForm({ routineId, exerciseId, movement, editing, previous, onDone }: RoutineSetFormProps) {
    const add = useAddRoutineSet()
    const update = useUpdateRoutineSet()
    const mutation = editing === null ? add : update
    const violations = useViolations(mutation.error)

    const source = editing ?? previous
    const [reps, setReps] = useState(source?.reps?.toString() ?? '')
    const [percentage, setPercentage] = useState(decimalField(source?.percentageOfOneRepMax ?? null))

    const submit = async () => {
        // Only what the movement takes is sent: a share without a load would be refused.
        const payload = {
            reps: parseDecimal(reps),
            percentageOfOneRepMax: movement.tracksWeight ? parseDecimal(percentage) : null,
        }

        try {
            if (editing === null) {
                await add.mutateAsync({ id: routineId, exerciseId, payload })
            } else {
                await update.mutateAsync({ id: routineId, setId: editing.id, payload })
            }
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <FormGrid columns={2}>
                <NumberField
                    label={movement.isUnilateral ? 'Répétitions / côté' : 'Répétitions'}
                    isMandatory
                    errors={violations.for('reps')}
                    min={1}
                    value={reps}
                    autoFocus
                    onChange={setReps}
                />

                {movement.tracksWeight && (
                    <DecimalField
                        label="Part du 1RM (%)"
                        isMandatory
                        errors={violations.for('percentageOfOneRepMax')}
                        placeholder="80"
                        value={percentage}
                        onChange={setPercentage}
                    />
                )}
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
