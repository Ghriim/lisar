import type { ReactNode } from 'react'
import { Alert, EmptyState, Loader } from './Feedback'

export interface ListGroup<TItem> {
    /** Omitted on a flat list: no heading is drawn. */
    label?: string
    items: TItem[]
}

interface DataListProps<TItem> {
    /** A flat list is one group without a label. */
    groups: ListGroup<TItem>[]
    renderItem: (item: TItem) => ReactNode
    keyOf: (item: TItem) => string | number
    loading?: boolean
    /** Set when the list itself could not be read, as opposed to being empty. */
    error?: string | null
    emptyText: string
}

/**
 * A list of things, grouped or not, with the three states a list can be in besides full:
 * loading, unreadable, empty. Screens stop spelling those out one by one.
 */
export function DataList<TItem>({
    groups,
    renderItem,
    keyOf,
    loading = false,
    error = null,
    emptyText,
}: DataListProps<TItem>) {
    if (loading) {
        return <Loader />
    }

    if (error !== null) {
        return <Alert>{error}</Alert>
    }

    const isEmpty = groups.every((group) => group.items.length === 0)

    if (isEmpty) {
        return <EmptyState>{emptyText}</EmptyState>
    }

    return (
        <>
            {groups.map((group, index) => (
                <div key={group.label ?? index} className="list-group">
                    {group.label !== undefined && <h2 className="list-group-heading">{group.label}</h2>}

                    {group.items.map((item) => (
                        <div key={keyOf(item)}>{renderItem(item)}</div>
                    ))}
                </div>
            ))}
        </>
    )
}
