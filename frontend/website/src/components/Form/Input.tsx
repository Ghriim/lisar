import type { InputHTMLAttributes } from 'react'

type InputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'onChange' | 'value'> & {
    value: string
    onChange: (value: string) => void
    isMandatory?: boolean
}

/** The one `<input>` of the app, which every field below draws. Not exported out of the folder. */
export function Input({ value, onChange, isMandatory = false, ...rest }: InputProps) {
    return (
        <input
            {...rest}
            className="field-input"
            value={value}
            required={isMandatory}
            onChange={(event) => onChange(event.target.value)}
        />
    )
}
