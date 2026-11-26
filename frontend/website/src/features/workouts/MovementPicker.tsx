import { useState } from 'react'
import type { WorkoutBlockExercisePayload, WorkoutMovementChoice } from '../../api/types'
import {
    Alert,
    Button,
    ChoiceField,
    DurationField,
    EmptyState,
    Form,
    FormActions,
    Loader,
    TextField,
    useViolations,
} from '../../components'
import { useWorkoutMovements } from './queries'
import { failureOf, parseDuration } from './workoutFormat'

interface MovementPickerProps {
    /** How many may be picked: one when a movement joins a block, up to six for a new block. */
    max: number
    pending: boolean
    failure: unknown
    /** Where the API puts what it says about the rest of the movement picked `index`-th. */
    restFieldOf: (index: number) => string
    onSubmit: (exercises: WorkoutBlockExercisePayload[]) => void
    onCancel: () => void
    /** Which of the movements on offer this picker shows: all of them unless said otherwise. */
    accepts?: (movement: WorkoutMovementChoice) => boolean
}

/**
 * The movements on offer, by family, each a chip to pick. Picking several makes a superset, in
 * the order they were picked — which the line above the chips says back. Each one picked gets a
 * rest of its own, empty until someone types one: none means no timer after its sets.
 */
export function MovementPicker({ max, pending, failure, restFieldOf, onSubmit, onCancel, accepts }: MovementPickerProps) {
    const movements = useWorkoutMovements()
    const [search, setSearch] = useState('')
    const [picked, setPicked] = useState<number[]>([])
    const [rests, setRests] = useState<Record<number, string>>({})
    const violations = useViolations(failure)

    const all = (movements.data ?? []).filter((movement) => accepts === undefined || accepts(movement))
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

    const submit = () => {
        onSubmit(picked.map((movementId) => ({ movementId, restInSeconds: parseDuration(rests[movementId] ?? '') })))
    }

    const restErrors = picked.map((_, index) => violations.for(restFieldOf(index)))
    // A rest refused is said under its field; anything else, once, above the buttons.
    const error = restErrors.some((errors) => errors.length > 0) ? null : failureOf(failure)

    return (
        <Form onSubmit={submit}>
            <TextField label="Chercher" value={search} autoFocus onChange={setSearch} />

            {max > 1 && (
                <p className="tracker-note" style={{ margin: 0 }}>
                    {picked.length === 0
                        ? `Un mouvement, ou jusqu’à ${max} pour un superset.`
                        : picked.map((id, index) => `${index + 1}. ${byId.get(id)?.name ?? ''}`).join('  ·  ')}
                </p>
            )}

            {/* What scrolls, in its fixed-height window: the search above and the buttons below stay put. */}
            <div className="modal-scroll">
                <ChoiceField
                    label={max === 1 ? 'Mouvement' : 'Mouvements'}
                    hideLabel
                    choices={families.map((family) => ({
                        label: family.name,
                        choices: family.movements.map((movement) => ({ value: movement.id, label: movement.name })),
                    }))}
                    chosen={picked}
                    onToggle={toggle}
                    empty={movements.isPending ? <Loader /> : <EmptyState>Aucun mouvement ne correspond.</EmptyState>}
                />
            </div>

            {picked.map((id, index) => (
                <DurationField
                    key={id}
                    label={`Repos${max === 1 ? '' : ` · ${byId.get(id)?.name ?? ''}`} (s ou m:ss)`}
                    errors={restErrors[index]}
                    placeholder="1:30"
                    value={rests[id] ?? ''}
                    onChange={(rest) => setRests({ ...rests, [id]: rest })}
                />
            ))}

            {error !== null && <Alert>{error}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onCancel}>
                    Annuler
                </Button>
                <Button submit disabled={pending || picked.length === 0}>
                    {pending ? 'Ajouter…' : 'Ajouter'}
                </Button>
            </FormActions>
        </Form>
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
