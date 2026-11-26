import type { HabitCatalogItem } from '../../api/types'
import { Button, Chip, DataList, ListItem } from '../../components'
import { groupByCategoryIfAny } from '../categories/groupByCategory'
import { useHabitCatalog, useSubscribeHabit, useUnsubscribeHabit } from './queries'

/**
 * The subscribe window: every habit the catalogue offers, each with a way to take it on or drop
 * it. Dropping keeps its history — resuming later picks the run back up.
 */
export function HabitCatalog() {
    const catalog = useHabitCatalog()
    const subscribe = useSubscribeHabit()
    const unsubscribe = useUnsubscribeHabit()

    const pending = subscribe.isPending || unsubscribe.isPending

    return (
        // Under their category, like the panel the habits land in; the chip would repeat the heading.
        <DataList<HabitCatalogItem>
            groups={groupByCategoryIfAny(catalog.data ?? [], (item) => item.category)}
            keyOf={(item) => item.habitId}
            loading={catalog.isPending}
            error={catalog.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
            emptyText="Aucune habitude au catalogue pour le moment."
            renderItem={(item) => {
                return (
                    <ListItem
                        title={item.name}
                        meta={item.sourceKind === 'tracker' ? <Chip>Automatique</Chip> : undefined}
                        actions={
                            item.isSubscribed ? (
                                <Button variant="quiet" disabled={pending} onClick={() => unsubscribe.mutate(item.habitId)}>
                                    Retirer
                                </Button>
                            ) : (
                                <Button variant="primary" disabled={pending} onClick={() => subscribe.mutate(item.habitId)}>
                                    Suivre
                                </Button>
                            )
                        }
                    />
                )
            }}
        />
    )
}
