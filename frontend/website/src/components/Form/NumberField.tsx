import { FieldFrame, type FieldProps } from './Field'
import { Input } from './Input'

interface NumberFieldProps extends FieldProps {
    value: string
    onChange: (value: string) => void
    min?: number
    max?: number
    /** Below 1, the keyboard offers a decimal separator. */
    step?: number
    placeholder?: string
    autoFocus?: boolean
}

/** A number the browser's own control can hold: a count, a weight to the gram. */
export function NumberField({ label, isMandatory = false, errors, disabled, value, onChange, min, max, step, placeholder, autoFocus }: NumberFieldProps) {
    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors}>
            <Input
                type="number"
                inputMode={step === undefined || Number.isInteger(step) ? 'numeric' : 'decimal'}
                min={min}
                max={max}
                step={step}
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
