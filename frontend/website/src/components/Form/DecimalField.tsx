import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface DecimalFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    autoFocus?: boolean
}

/** A decimal typed the French way, with a comma: a load, a share of a 1RM. Parsed by the caller. */
export function DecimalField({ label, isMandatory = false, errors, disabled, value, onChange, placeholder, autoFocus }: DecimalFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                inputMode="decimal"
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
