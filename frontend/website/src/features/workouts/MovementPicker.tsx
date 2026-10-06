import { type FormEvent, useState } from 'react'
import type { WorkoutMovementChoice } from '../../api/types'
import { Alert, Button, EmptyState, Field, FormActions, Loader, Stack, TextInput, ToggleChip } from '../../components'
import { useWorkoutMovements } from './queries'
import { failureOf } from './workoutFormat'

interface MovementPickerProps {
    /** How many may be picked: one when a movement joins a block, up to six for a new block. */
    max: number
    pending: boolean
    failure: unknown
    onSubmit: (movementIds: number[]) => void
    onCancel: () => void
}

/**
 * The movements on offer, by family, each a chip to pick. Picking several makes a superset, in
 * the order they were picked — which the line above the chips says back.
 */
export function MovementPicker({ max, pending, failure, onSubmit, onCancel }: MovementPickerProps) {
    const movements = useWorkoutMovements()
    const [search, setSearch] = useState('')
    const [picked, setPicked] = useState<number[]>([])

    const all = movements.data ?? []
    const byId = new Map(all.map((movement) => [movement.id, movement]))
    const families = groupByFamily(all.filter((movement) => matches(movement, search)))

    const toggle = (id: number) => {
        if (picked.includes(id)) {
            setPicked(picked.filter((one) => one !== id))
        } else if (max === 1) {
            setPicked([id])
        } else if (picked.length < max) {
            setPicked([...picked, id])
        }
    }

    const submit = (event: FormEvent) => {
        event.preventDefault()
        onSubmit(picked)
    }

    const error = failureOf(failure)

    return (
        <form className="form-grid" onSubmit={submit}>
            <Field label="Chercher">
                <TextInput value={search} autoFocus onChange={(event) => setSearch(event.target.value)} />
            </Field>

            {max > 1 && (
                <p className="tracker-note" style={{ margin: 0 }}>
                    {picked.length === 0
                        ? `Un mouvement, ou jusqu’à ${max} pour un superset.`
                        : picked.map((id, index) => `${index + 1}. ${byId.get(id)?.name ?? ''}`).join('  ·  ')}
                </p>
            )}

            {movements.isPending ? (
                <Loader />
            ) : families.length === 0 ? (
                <EmptyState>Aucun mouvement ne correspond.</EmptyState>
            ) : (
                <Stack>
                    {families.map((family) => (
                        <div key={family.name} className="movement-family">
                            <span className="field-label">{family.name}</span>
                            <div className="tag-suggestions">
                                {family.movements.map((movement) => (
                                    <ToggleChip
                                        key={movement.id}
                                        pressed={picked.includes(movement.id)}
                                        onToggle={() => toggle(movement.id)}
                                    >
                                        {movement.name}
                                    </ToggleChip>
                                ))}
                            </div>
                        </div>
                    ))}
                </Stack>
            )}

            {error !== null && <Alert>{error}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onCancel}>
                    Annuler
                </Button>
                <Button submit disabled={pending || picked.length === 0}>
                    {pending ? 'Ajouter…' : 'Ajouter'}
                </Button>
            </FormActions>
        </form>
    )
}

function matches(movement: WorkoutMovementChoice, search: string): boolean {
    const needle = normalise(search.trim())

    return needle === '' || normalise(movement.name).includes(needle) || normalise(movement.movementFamilyName).includes(needle)
}

/** Accents and case aside: « developpe » finds « Développé couché ». */
function normalise(text: string): string {
    return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}

/** The API answers by name; this gathers them under their family, families by name too. */
function groupByFamily(movements: WorkoutMovementChoice[]): { name: string; movements: WorkoutMovementChoice[] }[] {
    const families = new Map<string, WorkoutMovementChoice[]>()

    for (const movement of movements) {
        families.set(movement.movementFamilyName, [...(families.get(movement.movementFamilyName) ?? []), movement])
    }

    return [...families.entries()]
        .sort(([one], [other]) => one.localeCompare(other, 'fr'))
        .map(([name, members]) => ({ name, movements: members }))
}
