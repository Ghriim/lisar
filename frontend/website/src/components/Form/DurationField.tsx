import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface DurationFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    autoFocus?: boolean
}

/** A duration typed as seconds or m:ss — a rest, a plank. Parsed by the caller. */
export function DurationField({ label, isMandatory = false, errors, disabled, value, onChange, placeholder, autoFocus }: DurationFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                inputMode="numeric"
                placeholder={placeholder}
                autoFocus={autoFocus}
                value={value}
                onChange={onChange}
                isMandatory={isMandatory}
                disabled={disabled}
            />
        </FieldFrame>
    )
}
