import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import { hydrationWord } from '../api/hydrationWords'
import { HYDRATION_ICONS, type HydrationPreset, type HydrationPresetPayload } from '../api/types'
import {
    Button,
    ConfirmButton,
    DataTable,
    FormModal,
    HydrationIcon,
    NumberField,
    Page,
    Paragraph,
    Row,
    SelectField,
    useNotifier,
} from '../components'

/**
 * The quantities people log in one tap: an icon and a volume, no label — the icon and the volume
 * say it, and a word would have to be translated by each front end anyway.
 */
export function HydrationPresetsPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A shortcut: editing that one. */
    const [editing, setEditing] = useState<HydrationPreset | null | undefined>(undefined)

    const presets = useQuery({ queryKey: ['hydration-presets'], queryFn: api.fetchHydrationPresets })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['hydration-presets'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: HydrationPresetPayload }) =>
            id === null ? api.createHydrationPreset(payload) : api.updateHydrationPreset(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Raccourci enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteHydrationPreset(id),
        onSuccess: async () => {
            notify.success('Raccourci supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    return (
        <Page
            title="Raccourcis d’hydratation"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <Paragraph muted>
                Proposés à tout le monde, du plus petit volume au plus grand. Une entrée copie le
                volume au moment où elle est saisie : corriger ou supprimer un raccourci ne change
                rien à ce qui a déjà été bu.
            </Paragraph>

            <DataTable<HydrationPreset>
                rows={presets.data ?? []}
                rowKey={(preset) => preset.id}
                loading={presets.isPending}
                emptyText="Aucun raccourci"
                columns={[
                    {
                        key: 'icon',
                        title: 'Icône',
                        width: 180,
                        render: (preset) => (
                            <Row gap={8}>
                                <HydrationIcon code={preset.icon} />
                                {hydrationWord(preset.icon)}
                            </Row>
                        ),
                    },
                    {
                        key: 'volume',
                        title: 'Volume',
                        render: (preset) => `${preset.volumeInMillilitres} mL`,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (preset) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(preset)}>
                                    Modifier
                                </Button>
                                <ConfirmButton
                                    question="Supprimer ce raccourci ?"
                                    loading={remove.isPending && remove.variables === preset.id}
                                    onConfirm={() => remove.mutate(preset.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<HydrationPresetPayload>
                    title={editing === null ? 'Nouveau raccourci' : 'Modifier le raccourci'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(payload) => save.mutate({ id: editing?.id ?? null, payload })}
                    initialValues={{
                        icon: editing?.icon ?? HYDRATION_ICONS[0],
                        volumeInMillilitres: editing?.volumeInMillilitres ?? 250,
                    }}
                >
                    <SelectField
                        name="icon"
                        label="Icône"
                        required
                        hint="Un code, pas une image : chaque front la dessine avec son propre jeu d’icônes."
                        options={HYDRATION_ICONS.map((code) => ({
                            value: code,
                            label: (
                                <Row gap={8}>
                                    <HydrationIcon code={code} size={16} />
                                    {hydrationWord(code)}
                                </Row>
                            ),
                        }))}
                    />

                    <NumberField
                        name="volumeInMillilitres"
                        label="Volume (mL)"
                        required
                        min={1}
                        max={5000}
                    />
                </FormModal>
            )}
        </Page>
    )
}
