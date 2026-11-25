import { useMandatory } from './useMandatory'

/** What every field of a `Form` takes, whatever it holds. */
export interface FieldProps {
    name: string
    label: string
    /** Puts an asterisk after the label, the legend at the top of the form, and refuses it empty. */
    isMandatory?: boolean
    placeholder?: string
    /** A line under the field explaining what it does to the rest of the app. */
    hint?: string
    autoFocus?: boolean
    disabled?: boolean
}

/** The rules Ant checks before submitting, and the form told the field is mandatory. */
export function useMandatoryRules(isMandatory: boolean) {
    useMandatory(isMandatory)

    return isMandatory ? [{ required: true, message: 'Requis' }] : undefined
}
