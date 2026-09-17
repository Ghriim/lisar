/** The one date format the back-office reads. */
export function formatDate(value: string): string {
    return new Date(value).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })
}
