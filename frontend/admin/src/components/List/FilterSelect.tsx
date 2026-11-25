import { Select } from 'antd'

interface FilterSelectProps<TValue extends string | number> {
    /** Shown while nothing is picked — which is the unfiltered list, so it names the filter. */
    placeholder: string
    value: TValue | undefined
    onChange: (value: TValue | undefined) => void
    options: { value: TValue; label: string }[]
    width?: number
}

/**
 * A list filter other than the active one. It opens empty, meaning "no filter", and its cross
 * takes it back there: nothing is narrowed until someone narrows it.
 */
export function FilterSelect<TValue extends string | number>({
    placeholder,
    value,
    onChange,
    options,
    width = 180,
}: FilterSelectProps<TValue>) {
    return (
        <Select<TValue>
            allowClear
            placeholder={placeholder}
            value={value}
            options={options}
            onChange={(next) => onChange(next ?? undefined)}
            style={{ width }}
        />
    )
}
