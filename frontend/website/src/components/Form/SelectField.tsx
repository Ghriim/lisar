import { FieldFrame, type FieldProps } from './Field'

export interface Option {
    value: string
    label: string
}

interface SelectFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    options: Option[]
    /** The empty choice, always first — nothing is preselected in this app. */
    placeholder: string
}

/** One choice out of a list. */
export function SelectField({ label, isMandatory = false, errors, disabled, value, onChange, options, placeholder }: SelectFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <select
                className="field-input"
                value={value}
                required={isMandatory}
                disabled={disabled}
                onChange={(event) => onChange(event.target.value)}
            >
                <option value="">{placeholder}</option>

                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FieldFrame>
    )
}
