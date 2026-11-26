import { useState } from 'react'
import { Button, Form, FormActions, TextField, useViolations } from '../../components'

interface TodoTitleFormProps {
    /** The title it opens on: empty for a new TODO, the current one to rename it. */
    initial?: string
    /** « Créer » or « Renommer ». */
    submitLabel: string
    save: (title: string) => Promise<unknown>
    pending: boolean
    failure: unknown
    onDone: () => void
}

/** A TODO's title, to create one or to rename it: unique among the account's, 125 characters at most. */
export function TodoTitleForm({ initial = '', submitLabel, save, pending, failure, onDone }: TodoTitleFormProps) {
    const [title, setTitle] = useState(initial)
    const violations = useViolations(failure)

    const submit = async () => {
        try {
            await save(title)
            onDone()
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <TextField
                label="Titre"
                isMandatory
                errors={[...violations.for('title'), ...violations.for('id')]}
                value={title}
                maxLength={125}
                placeholder="Maison, Travail, Projet…"
                onChange={setTitle}
                autoFocus
            />

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button variant="primary" submit disabled={pending}>
                    {submitLabel}
                </Button>
            </FormActions>
        </Form>
    )
}
