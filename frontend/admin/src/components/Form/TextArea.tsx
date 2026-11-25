import { Input } from 'antd'

interface TextAreaProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    rows?: number
}

/**
 * Standalone, outside a Form: the note box on an account is a single control, not a form, so it
 * takes a value and a handler rather than a field name.
 */
export function TextArea({ placeholder, rows = 3, value, onChange }: TextAreaProps) {
    return (
        <Input.TextArea
            rows={rows}
            value={value}
            placeholder={placeholder}
            onChange={(event) => onChange(event.target.value)}
        />
    )
}
