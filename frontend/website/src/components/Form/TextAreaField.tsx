import { FieldFrame, type FieldProps } from './Field'

interface TextAreaFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    rows?: number
    maxLength?: number
    placeholder?: string
    autoFocus?: boolean
}

/** Text over several lines: a detail, a note. */
export function TextAreaField({
    label,
    isMandatory = false,
    errors,
    disabled,
    value,
    onChange,
    rows,
    maxLength,
    placeholder,
    autoFocus,
}: TextAreaFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <textarea
                className="field-input"
                value={value}
                rows={rows}
                maxLength={maxLength}
                placeholder={placeholder}
                autoFocus={autoFocus}
                required={isMandatory}
                disabled={disabled}
                onChange={(event) => onChange(event.target.value)}
            />
        </FieldFrame>
    )
}
