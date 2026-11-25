import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface EmailFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    autoFocus?: boolean
}

/** An email address. */
export function EmailField({ label, isMandatory = false, errors, disabled, value, onChange, autoFocus }: EmailFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                type="email"
                autoComplete="email"
                autoFocus={autoFocus}
                value={value}
                onChange={onChange}
                isMandatory={isMandatory}
                disabled={disabled}
            />
        </FieldFrame>
    )
}
