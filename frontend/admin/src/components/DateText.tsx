import { Text } from './Text'

interface DateTextProps {
    value: string | null
    /** What to show when there is no date. "Jamais" reads better than a dash for a sign-in. */
    fallback?: string
}

/** The one date format the back-office reads. */
export function DateText({ value, fallback = '—' }: DateTextProps) {
    if (value === null) {
        return <Text muted>{fallback}</Text>
    }

    return <>{new Date(value).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}</>
}
