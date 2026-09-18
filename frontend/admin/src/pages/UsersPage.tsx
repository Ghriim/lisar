import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { User } from '../api/types'
import {
    Button,
    DataTable,
    DateText,
    LinkText,
    ListToolbar,
    Page,
    RoleTag,
    Row,
    StatusTag,
    useNotifier,
} from '../components'
import { UserDrawer } from './UserDrawer'

type StatusFilter = 'all' | 'active' | 'inactive'

const PER_PAGE = 25

export function UsersPage() {
    const notify = useNotifier()
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
            notify.success(user.isActive ? 'Compte réactivé.' : 'Compte désactivé.')
            await queryClient.invalidateQueries({ queryKey: ['users'] })
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    return (
        <Page title="Comptes">
            <ListToolbar<StatusFilter>
                searchPlaceholder="Nom ou adresse"
                onSearch={(term) => {
                    setSearch(term)
                    setPage(1)
                }}
                filter={{
                    value: status,
                    onChange: (value) => {
                        setStatus(value)
                        setPage(1)
                    },
                    options: [
                        { label: 'Tous', value: 'all' },
                        { label: 'Actifs', value: 'active' },
                        { label: 'Désactivés', value: 'inactive' },
                    ],
                }}
            />

            <DataTable<User>
                rows={users.data?.items ?? []}
                rowKey={(user) => user.id}
                loading={users.isPending}
                emptyText="Aucun compte"
                pagination={{
                    page,
                    perPage: PER_PAGE,
                    total: users.data?.total ?? 0,
                    onChange: setPage,
                }}
                columns={[
                    {
                        key: 'username',
                        title: 'Nom',
                        render: (user) => (
                            <Row gap={8}>
                                <LinkText onClick={() => setOpened(user)}>{user.username}</LinkText>
                                <RoleTag role={user.role} />
                            </Row>
                        ),
                    },
                    { key: 'email', title: 'Adresse', render: (user) => user.email },
                    {
                        key: 'status',
                        title: 'Statut',
                        render: (user) => <StatusTag isActive={user.isActive} />,
                    },
                    {
                        key: 'lastSignedInAt',
                        title: 'Dernière connexion',
                        render: (user) => <DateText value={user.lastSignedInAt} fallback="Jamais" />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (user) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setOpened(user)}>
                                    Consulter
                                </Button>
                                <Button
                                    size="small"
                                    variant={user.isActive ? 'danger' : 'default'}
                                    loading={toggle.isPending && toggle.variables?.id === user.id}
                                    onClick={() => toggle.mutate(user)}
                                >
                                    {user.isActive ? 'Désactiver' : 'Réactiver'}
                                </Button>
                            </Row>
                        ),
                    },
                ]}
            />

            {opened !== null && <UserDrawer user={opened} onClose={() => setOpened(null)} />}
        </Page>
    )
}
