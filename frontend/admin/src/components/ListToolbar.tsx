import { Input, Segmented } from 'antd'
import { Row } from './Layout'

export interface FilterOption<TValue extends string> {
    label: string
    value: TValue
}

interface ListToolbarProps<TValue extends string> {
    /** Omit the pair to drop the search box: a list filtered only by status has nothing to search. */
    searchPlaceholder?: string
    onSearch?: (term: string) => void
    filter?: {
        value: TValue
        options: FilterOption<TValue>[]
        onChange: (value: TValue) => void
    }
}

/** What sits above a list: an optional search box, and at most one filter. */
export function ListToolbar<TValue extends string>({
    searchPlaceholder,
    onSearch,
    filter,
}: ListToolbarProps<TValue>) {
    return (
        <Row gap={16} wrap style={{ marginBottom: 16 }}>
            {onSearch !== undefined && (
                <Input.Search
                    placeholder={searchPlaceholder}
                    allowClear
                    style={{ maxWidth: 280 }}
                    onSearch={onSearch}
                />
            )}

            {filter !== undefined && (
                <Segmented<TValue>
                    value={filter.value}
                    options={filter.options}
                    onChange={filter.onChange}
                />
            )}
        </Row>
    )
}
