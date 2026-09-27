import { Input } from 'antd'
import type { ReactNode } from 'react'
import { Row } from './Layout'

interface ListToolbarProps {
    /** Omit the pair to drop the search box: a list filtered only by status has nothing to search. */
    searchPlaceholder?: string
    onSearch?: (term: string) => void
    /** Filters on every keystroke rather than on Enter: for a list held whole in memory. */
    searchAsYouType?: boolean
    /** The filters, if any: an ActiveFilter for an active/inactive one, then FilterSelects. */
    children?: ReactNode
}

/** What sits above a list: an optional search box, then its filters. */
export function ListToolbar({ searchPlaceholder, onSearch, searchAsYouType = false, children }: ListToolbarProps) {
    return (
        <Row gap={16} wrap style={{ marginBottom: 16 }}>
            {onSearch !== undefined && (
                <Input.Search
                    placeholder={searchPlaceholder}
                    allowClear
                    style={{ maxWidth: 280 }}
                    onSearch={onSearch}
                    onChange={searchAsYouType ? (event) => onSearch(event.target.value) : undefined}
                />
            )}

            {children}
        </Row>
    )
}
