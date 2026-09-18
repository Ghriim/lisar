import { App } from 'antd'
import { ApiError } from '../api/client'
import { summarise } from '../api/violations'

export interface Notifier {
    success: (message: string) => void
    /** Turns whatever went wrong into the most precise sentence available. */
    failure: (failure: unknown, fallback: string) => void
}

/**
 * One place that knows how an API error becomes a message: the violations when the API sent any,
 * the fallback otherwise. Without it every screen rewrites the same three lines.
 */
export function useNotifier(): Notifier {
    const { message } = App.useApp()

    return {
        success: (text: string) => message.success(text),
        failure: (failure: unknown, fallback: string) => {
            message.error(
                failure instanceof ApiError && failure.violations !== null
                    ? summarise(failure.violations)
                    : fallback,
            )
        },
    }
}
