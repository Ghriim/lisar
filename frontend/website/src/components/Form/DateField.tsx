import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface DateFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    autoFocus?: boolean
}

/** A day, "YYYY-MM-DD". */
export function DateField({ label, isMandatory = false, errors, disabled, value, onChange, autoFocus }: DateFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                type="date"
                autoFocus={autoFocus}
                value={value}
                onChange={onChange}
                isMandatory={isMandatory}
                disabled={disabled}
            />
        </FieldFrame>
    )
}
