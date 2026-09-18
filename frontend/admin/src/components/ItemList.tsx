import { List } from 'antd'
import type { ReactNode } from 'react'
import { EmptyState } from './Feedback'

interface ItemListProps<TItem> {
    items: TItem[]
    loading?: boolean
    emptyText: string
    renderItem: (item: TItem) => ReactNode
}

/** A vertical list of things that are not a table: the notes on an account. */
export function ItemList<TItem>({ items, loading = false, emptyText, renderItem }: ItemListProps<TItem>) {
    return (
        <List
            loading={loading}
            dataSource={items}
            locale={{ emptyText: <EmptyState description={emptyText} /> }}
            renderItem={(item) => <List.Item>{renderItem(item)}</List.Item>}
        />
    )
}
