import { useCallback, useState, type FormEvent, type ReactNode } from 'react'
import { MandatoryFields, type RegisterMandatory } from './useMandatory'

interface FormProps {
    /** The default submission is already prevented: this is only what the form does. */
    onSubmit: () => void | Promise<void>
    children: ReactNode
}

/**
 * Every form in the app is one of these. It opens on the legend `* Champ obligatoire` as soon as
 * one of its fields is mandatory, and on nothing otherwise — see
 * docs/dev/frontend-conventions.md, §2.1.
 */
export function Form({ onSubmit, children }: FormProps) {
    const [mandatoryCount, setMandatoryCount] = useState(0)
    const register = useCallback<RegisterMandatory>(() => {
        setMandatoryCount((count) => count + 1)

        return () => setMandatoryCount((count) => count - 1)
    }, [])

    const submit = (event: FormEvent) => {
        event.preventDefault()
        void onSubmit()
    }

    return (
        <MandatoryFields.Provider value={register}>
            <form className="form-grid" onSubmit={submit}>
                {mandatoryCount > 0 && (
                    <p className="form-legend">
                        <span className="field-required">*</span> Champ obligatoire
                    </p>
                )}
                {children}
            </form>
        </MandatoryFields.Provider>
    )
}
