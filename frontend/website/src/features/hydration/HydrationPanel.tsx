import { Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../api/client'
import type { HydrationDay, HydrationEntry } from '../../api/types'
import { humanise } from '../../api/violations'
import {
    Alert,
    Button,
    DataList,
    EmptyState,
    Form,
    FormActions,
    IconButton,
    ListItem,
    NumberField,
    ProgressBar,
    Stack,
    useViolations,
} from '../../components'
import {
    useCorrectHydrationEntry,
    useHydrationPresets,
    useLogHydration,
    useRemoveHydrationEntry,
} from './queries'
import { iconFor, wordFor } from './hydrationIcons'

/**
 * Everything one does with the day's drinking: the shortcuts, a free field, and what has been
 * logged so far. The widget outside stays glanceable; this is where the work happens.
 */
export function HydrationPanel({ day }: { day: HydrationDay }) {
    const presets = useHydrationPresets()
    const log = useLogHydration()
    const correct = useCorrectHydrationEntry()
    const remove = useRemoveHydrationEntry()

    const [freeVolume, setFreeVolume] = useState('')
    const [editing, setEditing] = useState<{ id: number; volume: string } | null>(null)

    const logging = useViolations(log.error)
    const correcting = useViolations(correct.error)
    const removalFailed = remove.error instanceof ApiError ? remove.error : null

    const busy = log.isPending || correct.isPending || remove.isPending

    const submitFree = async () => {
        try {
            await log.mutateAsync(Number(freeVolume))
            setFreeVolume('')
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    const submitCorrection = async () => {
        if (editing === null) {
            return
        }

        try {
            await correct.mutateAsync({ id: editing.id, volumeInMillilitres: Number(editing.volume) })
            setEditing(null)
        } catch {
            // Same: the field reads them.
        }
    }

    return (
        <Stack>
            <div>
                <p className="tracker-value" style={{ margin: 0 }}>
                    {day.totalInMillilitres} <span className="tracker-note">/ {day.goalInMillilitres} mL</span>
                </p>
                <ProgressBar
                    value={day.totalInMillilitres}
                    max={day.goalInMillilitres}
                    label={`${day.totalInMillilitres} sur ${day.goalInMillilitres} millilitres`}
                />
            </div>

            <div className="shortcuts">
                {(presets.data ?? []).map((preset) => {
                    const Icon = iconFor(preset.icon)

                    return (
                        <button
                            key={preset.id}
                            type="button"
                            className="shortcut"
                            disabled={busy}
                            aria-label={`${wordFor(preset.icon)} · ${preset.volumeInMillilitres} mL`}
                            onClick={() => log.mutate(preset.volumeInMillilitres)}
                        >
                            <Icon size={20} strokeWidth={1.8} aria-hidden />
                            {preset.volumeInMillilitres} mL
                        </button>
                    )
                })}
            </div>

            <Form onSubmit={submitFree}>
                <NumberField
                    label="Autre quantité"
                    isMandatory
                    errors={logging.for('volumeInMillilitres')}
                    min={1}
                    max={5000}
                    value={freeVolume}
                    onChange={setFreeVolume}
                    placeholder="en millilitres"
                />

                <FormActions>
                    <Button variant="primary" submit disabled={busy}>
                        Ajouter
                    </Button>
                </FormActions>
            </Form>

            {removalFailed !== null && (
                <Alert>{humanise(removalFailed.violationsFor('id')[0] ?? 'action_failed')}</Alert>
            )}

            {day.entries.length === 0 ? (
                <EmptyState>Rien de bu pour l’instant.</EmptyState>
            ) : (
                <DataList<HydrationEntry>
                    groups={[{ label: 'Aujourd’hui', items: day.entries }]}
                    keyOf={(entry) => entry.id}
                    emptyText="Rien de bu pour l’instant."
                    renderItem={(entry) =>
                        editing?.id === entry.id ? (
                            <Form onSubmit={submitCorrection}>
                                <NumberField
                                    label="Quantité"
                                    isMandatory
                                    errors={correcting.for('volumeInMillilitres')}
                                    min={1}
                                    max={5000}
                                    value={editing.volume}
                                    onChange={(volume) => setEditing({ id: entry.id, volume })}
                                    autoFocus
                                />

                                <FormActions>
                                    <Button variant="quiet" onClick={() => setEditing(null)}>
                                        Annuler
                                    </Button>
                                    <Button variant="primary" submit disabled={busy}>
                                        Enregistrer
                                    </Button>
                                </FormActions>
                            </Form>
                        ) : (
                            <ListItem
                                title={`${entry.volumeInMillilitres} mL`}
                                note={formatTime(entry.recordedAt)}
                                actions={
                                    <>
                                        <IconButton
                                            icon={Pencil}
                                            label="Corriger"
                                            subject={`${entry.volumeInMillilitres} mL`}
                                            disabled={busy}
                                            onClick={() =>
                                                setEditing({
                                                    id: entry.id,
                                                    volume: String(entry.volumeInMillilitres),
                                                })
                                            }
                                        />
                                        <IconButton
                                            icon={Trash2}
                                            variant="danger"
                                            label="Retirer"
                                            subject={`${entry.volumeInMillilitres} mL`}
                                            disabled={busy}
                                            onClick={() => remove.mutate(entry.id)}
                                        />
                                    </>
                                }
                            />
                        )
                    }
                />
            )}
        </Stack>
    )
}

/** The hour it was logged at, on the clock the day is counted on — the API already converted. */
function formatTime(recordedAt: string | null): string | undefined {
    if (recordedAt === null) {
        return undefined
    }

    return recordedAt.slice(11, 16)
}
