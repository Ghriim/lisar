import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface TextFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    maxLength?: number
    autoComplete?: string
    autoFocus?: boolean
}

/** A line of text: a name, a title. */
export function TextField({ label, isMandatory = false, errors, disabled, value, onChange, placeholder, maxLength, autoComplete, autoFocus }: TextFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                placeholder={placeholder}
                maxLength={maxLength}
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
