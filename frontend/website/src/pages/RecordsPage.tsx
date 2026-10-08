import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import type { MovementPersonalBests } from '../api/types'
import { Alert, Chip, DataList, EmptyState, ListItem, Loader, Stack, SystemPanel, type ListGroup } from '../components'
import { personalBestAnchor } from '../features/records/personalBestFormat'
import { PersonalBestRecordItem } from '../features/records/PersonalBestRecordItem'
import { usePersonalBests } from '../features/workouts/queries'

/**
 * The account's records: those of whole workouts first, then every movement on offer, by family,
 * with its records — or none yet. Each record unfolds on its progression. Reached on one record —
 * from a set that beat it — the page opens its movement and brings it into view.
 */
export function RecordsPage() {
    const board = usePersonalBests()
    const target = useLocation().hash.slice(1)

    useEffect(() => {
        if (board.data !== undefined && target !== '') {
            document.getElementById(target)?.scrollIntoView({ block: 'center' })
        }
    }, [board.data, target])

    if (board.isPending) {
        return <Loader />
    }

    if (board.isError) {
        return <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
    }

    return (
        <Stack>
            <SystemPanel title="Records de séance">
                {board.data.sessions.length === 0 ? (
                    <EmptyState>Aucun record pour l’instant : termine une séance.</EmptyState>
                ) : (
                    board.data.sessions.map((record) => (
                        <PersonalBestRecordItem key={`${record.kind}-${record.tier ?? ''}`} record={record} />
                    ))
                )}
            </SystemPanel>

            <SystemPanel title="Records par mouvement">
                <DataList<MovementPersonalBests>
                    groups={byFamily(board.data.movements)}
                    keyOf={(movement) => movement.movementId}
                    emptyText="Aucun mouvement proposé."
                    renderItem={(movement) => <MovementItem movement={movement} target={target} />}
                />
            </SystemPanel>
        </Stack>
    )
}

function MovementItem({ movement, target }: { movement: MovementPersonalBests; target: string }) {
    const count = movement.records.length
    const holdsTarget = movement.records.some(
        (record) => personalBestAnchor({ ...record, movementId: movement.movementId }) === target,
    )

    return (
        <ListItem
            title={movement.movementName}
            note={count === 0 ? 'Aucun record' : `${count} record${count > 1 ? 's' : ''}`}
            meta={movement.isOffered ? undefined : <Chip>Retiré</Chip>}
            defaultUnfolded={holdsTarget}
        >
            {count > 0 &&
                movement.records.map((record) => (
                    <PersonalBestRecordItem key={`${record.kind}-${record.tier ?? ''}`} record={record} />
                ))}
        </ListItem>
    )
}

/** The API answers them by family then by name: the groups follow that order. */
function byFamily(movements: MovementPersonalBests[]): ListGroup<MovementPersonalBests>[] {
    const groups: ListGroup<MovementPersonalBests>[] = []
    for (const movement of movements) {
        const last = groups[groups.length - 1]
        if (last !== undefined && last.label === movement.movementFamilyName) {
            last.items.push(movement)
        } else {
            groups.push({ label: movement.movementFamilyName, items: [movement] })
        }
    }

    return groups
}
