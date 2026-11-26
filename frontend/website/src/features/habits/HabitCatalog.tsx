import { CalendarDays, Minus, Plus } from 'lucide-react'
import { useState } from 'react'
import type { HabitCatalogItem } from '../../api/types'
import { Chip, DataList, IconButton, ListItem, TextField } from '../../components'
import { groupByCategoryIfAny } from '../categories/groupByCategory'
import { formatHabitRhythmShort } from '../planning/planningFormat'
import { useHabitPlanningEntries } from '../planning/queries'
import { HabitSchedule } from './HabitSchedule'
import { useHabitCatalog, useSubscribeHabit, useUnsubscribeHabit } from './queries'

/** The habit whose planning is unfolded under its line — and whether it was taken on just now. */
interface Opened {
    habitId: number
    justSubscribed: boolean
}

/**
 * The subscribe window, and the one place a habit's planning is managed: every habit the
 * catalogue offers — a search on top narrows them — each saying whether it is kept by hand or by a
 * tracker and, once taken on, the days it counts, with a way to take it on or drop it. Taking one on unfolds its planning
 * under it, on the form of the « tous les jours » it starts with, to say at once which days it
 * counts; a kept habit's « Planifier » unfolds it again. Dropping keeps its history — resuming later
 * picks the run back up.
 */
export function HabitCatalog() {
    const catalog = useHabitCatalog()
    const subscribe = useSubscribeHabit()
    const unsubscribe = useUnsubscribeHabit()
    const [opened, setOpened] = useState<Opened | null>(null)
    const [search, setSearch] = useState('')
    const planning = useHabitPlanningEntries()
    const shown = (catalog.data ?? []).filter((item) => matches(item, search))

    const pending = subscribe.isPending || unsubscribe.isPending

    return (
        // The search stays on top, the list alone scrolls: the window is `tall`.
        <div className="modal-fill">
            <TextField label="Chercher" value={search} onChange={setSearch} placeholder="Lire, marcher…" autoFocus />

            <div className="modal-scroll">
                {/* Under their category, like the panel the habits land in; the chip would repeat the heading. */}
                <DataList<HabitCatalogItem>
                    groups={groupByCategoryIfAny(shown, (item) => item.category)}
                    keyOf={(item) => item.habitId}
                    loading={catalog.isPending}
                    error={catalog.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                    emptyText={search.trim() === '' ? 'Aucune habitude au catalogue pour le moment.' : 'Aucune habitude ne correspond.'}
                    renderItem={(item) => {
                        const isOpen = item.isSubscribed && opened?.habitId === item.habitId

                        return (
                            <div className={isOpen ? 'habit-catalog-item habit-catalog-item-open' : 'habit-catalog-item'}>
                                <ListItem
                                    title={item.name}
                                    note={item.isSubscribed ? (formatHabitRhythmShort(item.habitId, planning.data ?? []) ?? undefined) : undefined}
                                    meta={<Chip>{item.sourceKind === 'tracker' ? 'Automatique' : 'Manuel'}</Chip>}
                                    actions={
                                        item.isSubscribed ? (
                                            <>
                                                <IconButton
                                                    icon={CalendarDays}
                                                    label="Planifier"
                                                    subject={item.name}
                                                    expanded={isOpen}
                                                    onClick={() => setOpened(isOpen ? null : { habitId: item.habitId, justSubscribed: false })}
                                                />
                                                <IconButton
                                                    icon={Minus}
                                                    variant="danger"
                                                    label="Retirer"
                                                    subject={item.name}
                                                    disabled={pending}
                                                    onClick={() =>
                                                        unsubscribe.mutate(item.habitId, {
                                                            onSuccess: () => setOpened((current) => (current?.habitId === item.habitId ? null : current)),
                                                        })
                                                    }
                                                />
                                            </>
                                        ) : (
                                            <IconButton
                                                icon={Plus}
                                                variant="primary"
                                                label="Suivre"
                                                subject={item.name}
                                                disabled={pending}
                                                onClick={() =>
                                                    subscribe.mutate(item.habitId, {
                                                        onSuccess: () => setOpened({ habitId: item.habitId, justSubscribed: true }),
                                                    })
                                                }
                                            />
                                        )
                                    }
                                />

                                {isOpen && (
                                    <div className="habit-catalog-schedule">
                                        <HabitSchedule
                                            key={`${item.habitId}-${opened.justSubscribed ? 'new' : 'kept'}`}
                                            habitId={item.habitId}
                                            habitName={item.name}
                                            editFirst={opened.justSubscribed}
                                        />
                                    </div>
                                )}
                            </div>
                        )
                    }}
                />
            </div>
        </div>
    )
}

function matches(item: HabitCatalogItem, search: string): boolean {
    const needle = normalise(search.trim())

    return needle === '' || normalise(item.name).includes(needle) || normalise(item.category?.label ?? '').includes(needle)
}

/** Accents and case aside: « eau » finds « Boire de l'eau ». */
function normalise(text: string): string {
    return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}
