import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { App, Button, Card, Flex, Form, Input, InputNumber, Modal, Popconfirm, Space, Switch, Table, Tag } from 'antd'
import { useState } from 'react'
import { ApiError } from '../api/client'
import * as api from '../api/endpoints'
import type { Priority, PriorityPayload } from '../api/types'
import { summarise } from '../api/violations'

export function PrioritiesPage() {
    const { message } = App.useApp()
    const queryClient = useQueryClient()
    const [editing, setEditing] = useState<Priority | null | undefined>(undefined)

    const priorities = useQuery({ queryKey: ['priorities'], queryFn: api.fetchPriorities })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['priorities'] })

    const onError = (fallback: string) => (failure: unknown) => {
        message.error(
            failure instanceof ApiError && failure.violations !== null
                ? summarise(failure.violations)
                : fallback,
        )
    }

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: PriorityPayload }) =>
            id === null ? api.createPriority(payload) : api.updatePriority(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            message.success('Priorité enregistrée.')
            await refresh()
        },
        onError: onError('L’enregistrement a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deletePriority(id),
        onSuccess: async () => {
            message.success('Priorité supprimée.')
            await refresh()
        },
        onError: onError('La suppression a échoué.'),
    })

    return (
        <Card
            title="Priorités"
            extra={
                <Button type="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <Table<Priority>
                rowKey="id"
                loading={priorities.isPending}
                dataSource={priorities.data ?? []}
                pagination={false}
                columns={[
                    {
                        title: 'Libellé',
                        dataIndex: 'label',
                        render: (label: string, priority) => (
                            <Space>
                                <span
                                    aria-hidden
                                    style={{
                                        display: 'inline-block',
                                        width: 10,
                                        height: 10,
                                        borderRadius: '50%',
                                        background: priority.colour,
                                    }}
                                />
                                {label}
                                {priority.isDefault && <Tag color="blue">défaut</Tag>}
                            </Space>
                        ),
                    },
                    { title: 'Poids', dataIndex: 'weight', width: 100 },
                    { title: 'Couleur', dataIndex: 'colour', width: 140 },
                    {
                        title: '',
                        key: 'actions',
                        align: 'right',
                        render: (_, priority) => (
                            <Space>
                                <Button size="small" onClick={() => setEditing(priority)}>
                                    Modifier
                                </Button>
                                <Popconfirm
                                    title="Supprimer cette priorité ?"
                                    okText="Supprimer"
                                    cancelText="Annuler"
                                    onConfirm={() => remove.mutate(priority.id)}
                                >
                                    <Button size="small" danger loading={remove.isPending && remove.variables === priority.id}>
                                        Supprimer
                                    </Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <PriorityModal
                    priority={editing}
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(payload) => save.mutate({ id: editing?.id ?? null, payload })}
                />
            )}
        </Card>
    )
}

interface PriorityModalProps {
    priority: Priority | null
    pending: boolean
    onCancel: () => void
    onSubmit: (payload: PriorityPayload) => void
}

/**
 * Mounted only while open, so the form always starts from the priority being edited — and the
 * buttons follow the house rule: centred, cancel first, a verb and nothing else.
 */
function PriorityModal({ priority, pending, onCancel, onSubmit }: PriorityModalProps) {
    return (
        <Modal open title={priority === null ? 'Nouvelle priorité' : 'Modifier la priorité'} footer={null} onCancel={onCancel}>
            <Form<PriorityPayload>
                layout="vertical"
                requiredMark={false}
                initialValues={{
                    label: priority?.label ?? '',
                    weight: priority?.weight ?? 0,
                    colour: priority?.colour ?? '#3e63dd',
                    isDefault: priority?.isDefault ?? false,
                }}
                onFinish={onSubmit}
            >
                <Form.Item label="Libellé" name="label" rules={[{ required: true, message: 'Requis' }]}>
                    <Input autoFocus />
                </Form.Item>

                <Form.Item label="Poids" name="weight" extra="Plus le poids est faible, plus la priorité remonte.">
                    <InputNumber min={0} max={9999} style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item label="Couleur" name="colour" rules={[{ required: true, message: 'Requis' }]}>
                    <Input placeholder="#RRGGBB" />
                </Form.Item>

                <Form.Item
                    label="Priorité par défaut"
                    name="isDefault"
                    valuePropName="checked"
                    extra="La donner à celle-ci la retire à celle qui l’avait. Elle ne se retire jamais seule."
                >
                    <Switch disabled={priority?.isDefault === true} />
                </Form.Item>

                <Flex justify="center" gap={8}>
                    <Button onClick={onCancel}>Annuler</Button>
                    <Button type="primary" htmlType="submit" loading={pending}>
                        Enregistrer
                    </Button>
                </Flex>
            </Form>
        </Modal>
    )
}
