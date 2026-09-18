export interface Option {
    value: string
    label: string
}

interface SelectProps {
    value: string
    onChange: (value: string) => void
    options: Option[]
    /** The empty choice, always first — nothing is preselected in this app. */
    placeholder: string
}

export function Select({ value, onChange, options, placeholder }: SelectProps) {
    return (
        <select className="field-input" value={value} onChange={(event) => onChange(event.target.value)}>
            <option value="">{placeholder}</option>

            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    )
}
