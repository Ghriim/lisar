import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { App, Button, Card, Flex, Form, Input, Modal, Popconfirm, Space, Table, Typography } from 'antd'
import { useState } from 'react'
import { ApiError } from '../api/client'
import * as api from '../api/endpoints'
import type { Category } from '../api/types'
import { summarise } from '../api/violations'

/**
 * The common categories, the ones everyone picks from. What people create for themselves never
 * shows up here — the API does not serve it to this surface.
 */
export function CategoriesPage() {
    const { message } = App.useApp()
    const queryClient = useQueryClient()
    const [editing, setEditing] = useState<Category | null | undefined>(undefined)

    const categories = useQuery({ queryKey: ['categories'], queryFn: api.fetchCategories })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['categories'] })

    const onError = (fallback: string) => (failure: unknown) => {
        message.error(
            failure instanceof ApiError && failure.violations !== null
                ? summarise(failure.violations)
                : fallback,
        )
    }

    const save = useMutation({
        mutationFn: ({ id, label }: { id: number | null; label: string }) =>
            id === null ? api.createCategory(label) : api.updateCategory(id, label),
        onSuccess: async () => {
            setEditing(undefined)
            message.success('Catégorie enregistrée.')
            await refresh()
        },
        onError: onError('L’enregistrement a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteCategory(id),
        onSuccess: async () => {
            message.success('Catégorie supprimée.')
            await refresh()
        },
        onError: onError('La suppression a échoué.'),
    })

    return (
        <Card
            title="Catégories communes"
            extra={
                <Button type="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <Typography.Paragraph type="secondary">
                Visibles par tous les comptes. Les catégories qu’une personne crée pour elle-même
                n’apparaissent pas ici.
            </Typography.Paragraph>

            <Table<Category>
                rowKey="id"
                loading={categories.isPending}
                dataSource={categories.data ?? []}
                pagination={false}
                columns={[
                    { title: 'Libellé', dataIndex: 'label' },
                    {
                        title: '',
                        key: 'actions',
                        align: 'right',
                        render: (_, category) => (
                            <Space>
                                <Button size="small" onClick={() => setEditing(category)}>
                                    Renommer
                                </Button>
                                <Popconfirm
                                    title="Supprimer cette catégorie ?"
                                    okText="Supprimer"
                                    cancelText="Annuler"
                                    onConfirm={() => remove.mutate(category.id)}
                                >
                                    <Button size="small" danger loading={remove.isPending && remove.variables === category.id}>
                                        Supprimer
                                    </Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <Modal
                    open
                    title={editing === null ? 'Nouvelle catégorie' : 'Renommer la catégorie'}
                    footer={null}
                    onCancel={() => setEditing(undefined)}
                >
                    <Form<{ label: string }>
                        layout="vertical"
                        requiredMark={false}
                        initialValues={{ label: editing?.label ?? '' }}
                        onFinish={({ label }) => save.mutate({ id: editing?.id ?? null, label })}
                    >
                        <Form.Item label="Libellé" name="label" rules={[{ required: true, message: 'Requis' }]}>
                            <Input autoFocus />
                        </Form.Item>

                        <Flex justify="center" gap={8}>
                            <Button onClick={() => setEditing(undefined)}>Annuler</Button>
                            <Button type="primary" htmlType="submit" loading={save.isPending}>
                                Enregistrer
                            </Button>
                        </Flex>
                    </Form>
                </Modal>
            )}
        </Card>
    )
}
