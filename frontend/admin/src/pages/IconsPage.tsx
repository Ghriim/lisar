import { icons } from 'lucide-react'
import { useMemo, useState } from 'react'
import { IconGallery, ListToolbar, Page, Paragraph, useNotifier } from '../components'

const ALL_ICONS = Object.entries(icons)

/** "ArrowUpRight" and "arrow-up-right" both read as "arrowupright": either spelling finds it. */
const normalise = (text: string) => text.toLowerCase().replace(/[^a-z0-9]/g, '')

/**
 * Every icon lucide-react ships, not only the ones lisar uses: a place to shop for the next one.
 * Clicking an icon copies its name, ready to import.
 */
export function IconsPage() {
    const notify = useNotifier()
    const [term, setTerm] = useState('')

    const shown = useMemo(() => {
        const wanted = normalise(term)

        return '' === wanted ? ALL_ICONS : ALL_ICONS.filter(([name]) => normalise(name).includes(wanted))
    }, [term])

    const copy = (name: string) => {
        navigator.clipboard.writeText(name).then(
            () => notify.success(`« ${name} » copié.`),
            (failure: unknown) => notify.failure(failure, 'La copie a échoué.'),
        )
    }

    return (
        <Page title="Icônes">
            <Paragraph muted>
                Toutes les icônes de lucide-react, la bibliothèque des deux fronts — {shown.length} sur{' '}
                {ALL_ICONS.length}. Un clic copie le nom à importer.
            </Paragraph>

            <ListToolbar searchPlaceholder="Rechercher une icône" onSearch={setTerm} searchAsYouType />

            <IconGallery icons={shown} onPick={copy} />
        </Page>
    )
}
