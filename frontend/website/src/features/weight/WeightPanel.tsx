import { type FormEvent, useState } from 'react'
import type { Weight } from '../../api/types'
import { Button, Field, FormActions, Stack, TextInput, useViolations } from '../../components'
import { useSaveWeight } from './queries'
import { formatWeight, formatWhen, parseWeight } from './weightFormat'

/**
 * The one field there is to fill. It opens on the last known weight, whatever day it came from:
 * a weight moves by hundreds of grams, so the previous number is almost the next one and
 * retyping it in full is work for nothing.
 *
 * This is the one place the "nothing is preselected" rule does not hold, and deliberately: a
 * weight is a correction of the last one, not a choice among options.
 */
export function WeightPanel({ latest, onClose }: { latest: Weight; onClose: () => void }) {
    const save = useSaveWeight()
    const violations = useViolations(save.error)

    const [typed, setTyped] = useState(
        latest.weightInKilograms === null ? '' : latest.weightInKilograms.toFixed(2),
    )

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        try {
            await save.mutateAsync(parseWeight(typed))
            onClose()
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    return (
        <Stack>
            <p className="tracker-note" style={{ margin: 0 }}>
                {note(latest)}
            </p>

            <form className="form-grid" onSubmit={(event) => void submit(event)}>
                <Field label="Poids en kilogrammes" errors={violations.for('weightInKilograms')}>
                    <TextInput
                        type="number"
                        step="0.01"
                        min={20}
                        max={400}
                        value={typed}
                        onChange={(event) => setTyped(event.target.value)}
                        placeholder="72,40"
                        autoFocus
                        required
                    />
                </Field>

                <FormActions>
                    <Button variant="quiet" onClick={onClose}>
                        Annuler
                    </Button>
                    <Button variant="primary" submit disabled={save.isPending}>
                        {save.isPending ? 'Enregistrer…' : 'Enregistrer'}
                    </Button>
                </FormActions>
            </form>
        </Stack>
    )
}

/** What is about to happen, said in the one line it takes. */
function note(latest: Weight): string {
    if (latest.weightInKilograms === null || latest.day === null) {
        return 'La pesée est enregistrée pour aujourd’hui.'
    }

    if (latest.isFromToday) {
        return `Pesée du jour : ${formatWeight(latest.weightInKilograms)}. L’enregistrer à nouveau la corrige.`
    }

    const when = formatWhen(latest.day, latest.recordedAt, latest.isFromToday)

    return `Dernière pesée : ${formatWeight(latest.weightInKilograms)}, ${when}. La nouvelle compte pour aujourd’hui.`
}
