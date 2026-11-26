import { useId, type ReactNode } from 'react'
import { humanise } from '../../api/violations'
import { useMandatory } from './useMandatory'

/** What every field takes, whatever it holds. */
export interface FieldProps {
    label: string
    /** Puts an asterisk after the label, and the legend at the top of the form. */
    isMandatory?: boolean
    /** The error codes the API returned for this field, worded underneath. */
    errors?: string[]
    disabled?: boolean
    /**
     * Said to a screen reader, not shown: for a field whose purpose the window already says — the
     * movements of « Ajouter un exercice », right under its search.
     */
    hideLabel?: boolean
}

interface FieldFrameProps extends Omit<FieldProps, 'disabled'> {
    /**
     * A field holding one control is a `<label>` around it. A field holding several — a scale,
     * a set of chips — is a group, named by its label.
     */
    group?: boolean
    children: ReactNode
}

/**
 * The frame every field is drawn in: its label, the asterisk when it is mandatory, the control,
 * then whatever the API had to say about it. Only the fields of this folder use it.
 */
export function FieldFrame({ label, isMandatory = false, errors = [], group = false, hideLabel = false, children }: FieldFrameProps) {
    useMandatory(isMandatory)
    const labelId = useId()

    const heading = (
        <span className={hideLabel ? 'field-label visually-hidden' : 'field-label'} id={labelId}>
            {label}
            {isMandatory && (
                <span className="field-required" aria-hidden="true">
                    {' *'}
                </span>
            )}
        </span>
    )
    const messages = errors.map((error) => (
        <span key={error} className="field-error">
            {humanise(error)}
        </span>
    ))

    if (group) {
        return (
            <div className="field" role="group" aria-labelledby={labelId} aria-required={isMandatory || undefined}>
                {heading}
                {children}
                {messages}
            </div>
        )
    }

    return (
        <label className="field">
            {heading}
            {children}
            {messages}
        </label>
    )
}
