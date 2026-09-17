import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { App, Button, Card, Flex, Input, Segmented, Space, Table, Tag, Typography } from 'antd'
import { useState } from 'react'
import { ApiError } from '../api/client'
import * as api from '../api/endpoints'
import { ROLE_ADMIN, type User } from '../api/types'
import { summarise } from '../api/violations'
import { formatDate } from './formatDate'
import { UserDrawer } from './UserDrawer'

type StatusFilter = 'all' | 'active' | 'inactive'

const PER_PAGE = 25

export function UsersPage() {
    const { message } = App.useApp()
    const queryClient = useQueryClient()

    const [search, setSearch] = useState('')
    const [status, setStatus] = useState<StatusFilter>('all')
    const [page, setPage] = useState(1)
    const [opened, setOpened] = useState<User | null>(null)

    const filters = {
        search,
        isActive: status === 'all' ? undefined : status === 'active',
        page,
        perPage: PER_PAGE,
    }

    const users = useQuery({
        queryKey: ['users', filters],
        queryFn: () => api.fetchUsers(filters),
    })

    const toggle = useMutation({
        mutationFn: (user: User) => (user.isActive ? api.deactivateUser(user.id) : api.activateUser(user.id)),
        onSuccess: async (user) => {
            message.success(user.isActive ? 'Compte réactivé.' : 'Compte désactivé.')
            await queryClient.invalidateQueries({ queryKey: ['users'] })
        },
        onError: (failure) => {
            message.error(
                failure instanceof ApiError && failure.violations !== null
                    ? summarise(failure.violations)
                    : 'L’opération a échoué.',
            )
        },
    })

    return (
        <Card title="Comptes">
            <Flex gap={16} wrap style={{ marginBottom: 16 }}>
                <Input.Search
                    placeholder="Nom ou adresse"
                    allowClear
                    style={{ maxWidth: 280 }}
                    onSearch={(value) => {
                        setSearch(value)
                        setPage(1)
                    }}
                />

                <Segmented<StatusFilter>
                    value={status}
                    onChange={(value) => {
                        setStatus(value)
                        setPage(1)
                    }}
                    options={[
                        { label: 'Tous', value: 'all' },
                        { label: 'Actifs', value: 'active' },
                        { label: 'Désactivés', value: 'inactive' },
                    ]}
                />
            </Flex>

            <Table<User>
                rowKey="id"
                loading={users.isPending}
                dataSource={users.data?.items ?? []}
                pagination={{
                    current: page,
                    pageSize: PER_PAGE,
                    total: users.data?.total ?? 0,
                    showSizeChanger: false,
                    onChange: setPage,
                }}
                columns={[
                    {
                        title: 'Nom',
                        dataIndex: 'username',
                        render: (username: string, user) => (
                            <Space>
                                <Typography.Link onClick={() => setOpened(user)}>{username}</Typography.Link>
                                {user.role === ROLE_ADMIN && <Tag color="gold">admin</Tag>}
                            </Space>
                        ),
                    },
                    { title: 'Adresse', dataIndex: 'email' },
                    {
                        title: 'Statut',
                        dataIndex: 'isActive',
                        render: (isActive: boolean) => (
                            <Tag color={isActive ? 'green' : 'red'}>{isActive ? 'Actif' : 'Désactivé'}</Tag>
                        ),
                    },
                    {
                        title: 'Dernière connexion',
                        dataIndex: 'lastSignedInAt',
                        render: (date: string | null) => (date === null ? '—' : formatDate(date)),
                    },
                    {
                        title: '',
                        key: 'actions',
                        align: 'right',
                        render: (_, user) => (
                            <Space>
                                <Button size="small" onClick={() => setOpened(user)}>
                                    Consulter
                                </Button>
                                <Button
                                    size="small"
                                    danger={user.isActive}
                                    loading={toggle.isPending && toggle.variables?.id === user.id}
                                    onClick={() => toggle.mutate(user)}
                                >
                                    {user.isActive ? 'Désactiver' : 'Réactiver'}
                                </Button>
                            </Space>
                        ),
                    },
                ]}
            />

            {opened !== null && <UserDrawer user={opened} onClose={() => setOpened(null)} />}
        </Card>
    )
}
