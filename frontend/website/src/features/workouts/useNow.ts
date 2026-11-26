import { useEffect, useState } from 'react'

/** The time now, ticking every half minute while a workout runs, so its duration keeps up. */
export function useNow(ticking: boolean): Date {
    const [now, setNow] = useState(() => new Date())

    useEffect(() => {
        if (!ticking) {
            return undefined
        }

        const timer = setInterval(() => setNow(new Date()), 30 * 1000)

        return () => clearInterval(timer)
    }, [ticking])

    return now
}
