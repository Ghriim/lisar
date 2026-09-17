import type { InputHTMLAttributes, TextareaHTMLAttributes } from 'react'
import { humanise } from '../api/violations'

interface FieldProps {
    label: string
    errors?: string[]
    children: React.ReactNode
}

/** A labelled control, with whatever the API had to say about it underneath. */
export function Field({ label, errors = [], children }: FieldProps) {
    return (
        <label className="field">
            <span className="field-label">{label}</span>
            {children}
            {errors.map((error) => (
                <span key={error} className="field-error">
                    {humanise(error)}
                </span>
            ))}
        </label>
    )
}

export function TextInput(props: InputHTMLAttributes<HTMLInputElement>) {
    return <input {...props} className="field-input" />
}

export function TextArea(props: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea {...props} className="field-input" />
}
