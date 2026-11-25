import { createContext, useContext, useLayoutEffect } from 'react'

/** Called by a mandatory field when it shows; what it returns is called when it goes. */
export type RegisterMandatory = () => () => void

/** Provided by `Form`: the count of its mandatory fields on screen, which decides the legend. */
export const MandatoryFields = createContext<RegisterMandatory | null>(null)

/**
 * How a field tells the form around it that it is mandatory. It does so for as long as it is on
 * screen, so a field that shows only under some rule — the days of a weekly plan — brings the
 * legend with it, and takes it away again.
 */
export function useMandatory(isMandatory: boolean) {
    const register = useContext(MandatoryFields)

    // Before paint: the legend must not appear one frame after the form does.
    useLayoutEffect(() => {
        if (!isMandatory || register === null) {
            return undefined
        }

        return register()
    }, [isMandatory, register])
}
