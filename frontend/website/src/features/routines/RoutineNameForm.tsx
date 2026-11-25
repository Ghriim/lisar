import { useState } from 'react'
import { Alert, Button, Form, FormActions, TextField, useViolations } from '../../components'
import { failureOf } from '../workouts/workoutFormat'
import { ROUTINE_NAME_MAX_LENGTH } from './routineFormat'

interface RoutineNameFormProps {
    /** The name it carries now, or null for a new routine: the form then starts empty. */
    current: string | null
    /** Resolves once saved, rejects with what the API refused. */
    save: (name: string) => Promise<unknown>
    pending: boolean
    failure: unknown
    onDone: () => void
}

/** A routine's name, the one thing asked to create it: its blocks are added on its page. */
export function RoutineNameForm({ current, save, pending, failure, onDone }: RoutineNameFormProps) {
    const violations = useViolations(failure)
    const [name, setName] = useState(current ?? '')
    const verb = current === null ? 'Créer' : 'Enregistrer'

    const submit = async () => {
        try {
            await save(name.trim())
            onDone()
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <TextField
                label="Nom"
                isMandatory
                errors={violations.for('name')}
                value={name}
                autoFocus
                maxLength={ROUTINE_NAME_MAX_LENGTH}
                placeholder="Push, jambes, haut du corps…"
                onChange={setName}
            />

            {violations.isGeneral && <Alert>{failureOf(failure) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={pending}>
                    {pending ? `${verb}…` : verb}
                </Button>
            </FormActions>
        </Form>
    )
}
