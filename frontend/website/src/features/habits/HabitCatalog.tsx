import { Button, Chip, EmptyState, ListItem, Loader, Stack } from '../../components'
import { iconFor } from './habitIcons'
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
        <Stack>
            {catalog.isPending && <Loader />}

            {catalog.isSuccess && catalog.data.length === 0 && (
                <EmptyState>Aucune habitude au catalogue pour le moment.</EmptyState>
            )}

            {catalog.isSuccess &&
                catalog.data.map((item) => {
                    const Icon = iconFor(item.icon)

                    return (
                    <ListItem
                        key={item.habitId}
                        title={item.name}
                        icon={<Icon size={26} strokeWidth={1.6} aria-hidden />}
                        meta={item.sourceKind === 'tracker' ? <Chip>Automatique</Chip> : undefined}
                        actions={
                            item.isSubscribed ? (
                                <Button
                                    variant="quiet"
                                    disabled={pending}
                                    onClick={() => unsubscribe.mutate(item.habitId)}
                                >
                                    Retirer
                                </Button>
                            ) : (
                                <Button
                                    variant="primary"
                                    disabled={pending}
                                    onClick={() => subscribe.mutate(item.habitId)}
                                >
                                    Suivre
                                </Button>
                            )
                        }
                    />
                    )
                })}
        </Stack>
    )
}
