import { Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { HabitPlanningEntry } from '../../api/types'
import { Alert, Button, ConfirmDialog, DataList, FormActions, IconButton, ListItem, Stack } from '../../components'
import { PlanningEntryForm } from '../planning/PlanningEntryForm'
import { dayKey, formatRule, formatTimeSpan } from '../planning/planningFormat'
import { useDeleteHabitPlanningEntry, useHabitPlanningEntries } from '../planning/queries'
import { failureOf } from '../workouts/workoutFormat'

type View = { kind: 'list' } | { kind: 'create' } | { kind: 'edit'; entry: HabitPlanningEntry }

interface HabitScheduleProps {
    habitId: number
    habitName: string
}

/**
 * Which days a habit counts, from its own line: its entries, each changed in place, one added,
 * one deleted — but never the last, since a kept habit always has a planning. The same entries
 * the planning page shows, written through the same form.
 */
export function HabitSchedule({ habitId, habitName }: HabitScheduleProps) {
    const entries = useHabitPlanningEntries()
    const remove = useDeleteHabitPlanningEntry()
    const [view, setView] = useState<View>({ kind: 'list' })
    const [confirming, setConfirming] = useState<HabitPlanningEntry | null>(null)
    const back = () => setView({ kind: 'list' })

    const own = (entries.data ?? []).filter((entry) => entry.habitId === habitId)
    const isLast = own.length <= 1

    if (view.kind !== 'list') {
        return (
            <PlanningEntryForm
                kind="habit"
                habitId={habitId}
                editing={view.kind === 'edit' ? { kind: 'habit', entry: view.entry } : null}
                today={dayKey(new Date())}
                onDone={back}
            />
        )
    }

    return (
        <Stack>
            <DataList<HabitPlanningEntry>
                groups={[{ items: own }]}
                keyOf={(entry) => entry.id}
                loading={entries.isPending}
                error={entries.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                emptyText="Aucun planning."
                renderItem={(entry) => (
                    <ListItem
                        title={capitalised(formatRule(entry.schedule))}
                        note={formatTimeSpan(entry.schedule) ?? 'dans la journée'}
                        actions={
                            <>
                                <IconButton icon={Pencil} label="Modifier" subject={formatRule(entry.schedule)} onClick={() => setView({ kind: 'edit', entry })} />
                                {!isLast && (
                                    <IconButton
                                        icon={Trash2}
                                        variant="danger"
                                        label="Supprimer"
                                        subject={formatRule(entry.schedule)}
                                        disabled={remove.isPending}
                                        onClick={() => setConfirming(entry)}
                                    />
                                )}
                            </>
                        }
                    />
                )}
            />

            {remove.error !== null && <Alert>{failureOf(remove.error) ?? ''}</Alert>}

            <FormActions>
                <Button icon={Plus} onClick={() => setView({ kind: 'create' })}>
                    Ajouter
                </Button>
            </FormActions>

            {confirming !== null && (
                <ConfirmDialog
                    title="Supprimer l’entrée"
                    question={`« ${habitName} » ne sera plus planifiée ${formatRule(confirming.schedule)} : ces dates à venir disparaissent. Les jours déjà tenus restent.`}
                    confirmLabel="Supprimer"
                    onCancel={() => setConfirming(null)}
                    onConfirm={() => {
                        remove.mutate(confirming.id)
                        setConfirming(null)
                    }}
                />
            )}
        </Stack>
    )
}

function capitalised(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1)
}
