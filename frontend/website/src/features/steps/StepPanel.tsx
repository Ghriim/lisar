import { useState } from 'react'
import type { StepDay } from '../../api/types'
import { Button, Form, FormActions, NumberField, Stack, useViolations } from '../../components'
import { useSaveSteps } from './queries'

/**
 * The one field there is to fill. It opens on today's count when there is one: a correction is a
 * correction of that number, and retyping it in full to nudge it is work for nothing. On a day
 * nothing has been recorded yet it opens empty — there is no previous value of the day to correct.
 *
 * This is the measurement exception to "nothing is preselected": the field has a previous value
 * of its own, it is not a choice among options.
 */
export function StepPanel({ day, onClose }: { day: StepDay; onClose: () => void }) {
    const save = useSaveSteps()
    const violations = useViolations(save.error)

    const [typed, setTyped] = useState(day.countInSteps === null ? '' : String(day.countInSteps))

    const submit = async () => {
        try {
            await save.mutateAsync(Number(typed))
            onClose()
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    return (
        <Stack>
            <p className="tracker-note" style={{ margin: 0 }}>
                {note(day)}
            </p>

            <Form onSubmit={submit}>
                <NumberField
                    label="Nombre de pas"
                    isMandatory
                    errors={violations.for('countInSteps')}
                    step={1}
                    min={0}
                    max={200000}
                    value={typed}
                    onChange={setTyped}
                    placeholder="8000"
                    autoFocus
                />

                <FormActions>
                    <Button variant="quiet" onClick={onClose}>
                        Annuler
                    </Button>
                    <Button variant="primary" submit disabled={save.isPending}>
                        {save.isPending ? 'Enregistrer…' : 'Enregistrer'}
                    </Button>
                </FormActions>
            </Form>
        </Stack>
    )
}

/** What is about to happen, said in the one line it takes. */
function note(day: StepDay): string {
    if (day.countInSteps === null) {
        return 'Le total du jour est enregistré pour aujourd’hui.'
    }

    return `Total du jour : ${day.countInSteps} pas. L’enregistrer à nouveau le corrige.`
}
