import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface TimeFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    autoFocus?: boolean
}

/** A time of day, "HH:MM". */
export function TimeField({ label, isMandatory = false, errors, disabled, value, onChange, autoFocus }: TimeFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                type="time"
                autoFocus={autoFocus}
                value={value}
                onChange={onChange}
                isMandatory={isMandatory}
                disabled={disabled}
            />
        </FieldFrame>
    )
}
