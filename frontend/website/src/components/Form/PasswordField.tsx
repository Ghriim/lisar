import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface PasswordFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    /** Signing in reads the current one, registering writes a new one. */
    autoComplete: 'current-password' | 'new-password'
    autoFocus?: boolean
}

/** A password, hidden as it is typed. */
export function PasswordField({ label, isMandatory = false, errors, disabled, value, onChange, autoComplete, autoFocus }: PasswordFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                type="password"
                autoComplete={autoComplete}
                autoFocus={autoFocus}
                value={value}
                onChange={onChange}
                isMandatory={isMandatory}
                disabled={disabled}
            />
        </FieldFrame>
    )
}
