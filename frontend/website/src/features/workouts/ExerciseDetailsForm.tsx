import { type FormEvent, useState } from 'react'
import { Alert, Button, Field, FormActions, TextArea, TextInput, useViolations } from '../../components'
import { durationField, failureOf, parseDuration } from './workoutFormat'

export interface ExerciseDetails {
    note: string | null
    restInSeconds: number | null
}

interface ExerciseDetailsFormProps {
    /** What the movement carries now: the form opens on it. */
    exercise: ExerciseDetails
    /** Sends the whole of it; resolves once saved, rejects with what the API refused. */
    save: (details: ExerciseDetails) => Promise<unknown>
    pending: boolean
    failure: unknown
    onDone: () => void
}

/**
 * What a movement carries in a workout or a routine: the rest the timer counts after each of its
 * sets, and a note — a seat height, a grip, a pain to watch. Either left empty is cleared.
 */
export function ExerciseDetailsForm({ exercise, save, pending, failure, onDone }: ExerciseDetailsFormProps) {
    const violations = useViolations(failure)

    const [rest, setRest] = useState(durationField(exercise.restInSeconds))
    const [note, setNote] = useState(exercise.note ?? '')

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        try {
            await save({ note: note.trim() === '' ? null : note, restInSeconds: parseDuration(rest) })
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <Field label="Repos (s ou m:ss, facultatif)" errors={violations.for('restInSeconds')}>
                <TextInput
                    inputMode="numeric"
                    placeholder="1:30"
                    value={rest}
                    autoFocus
                    onChange={(event) => setRest(event.target.value)}
                />
            </Field>

            <Field label="Note (facultatif)" errors={violations.for('note')}>
                <TextArea value={note} rows={4} maxLength={5000} onChange={(event) => setNote(event.target.value)} />
            </Field>

            {violations.isGeneral && <Alert>{failureOf(failure) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={pending}>
                    {pending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </form>
    )
}
