import { Descriptions } from 'antd'
import type { ReactNode } from 'react'

export interface Description {
    label: string
    value: ReactNode
}

/** Label-and-value pairs, for the metadata of one row. */
export function DescriptionList({ items }: { items: Description[] }) {
    return (
        <Descriptions
            column={1}
            size="small"
            items={items.map((item, index) => ({
                key: `${index}`,
                label: item.label,
                children: item.value,
            }))}
        />
    )
}
