import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { App, Button, Descriptions, Divider, Drawer, Empty, Flex, Input, List, Tag, Typography } from 'antd'
import { useState } from 'react'
import { ApiError } from '../api/client'
import * as api from '../api/endpoints'
import type { User } from '../api/types'
import { summarise } from '../api/violations'
import { formatDate } from './formatDate'

interface UserDrawerProps {
    user: User
    onClose: () => void
}

/**
 * An account's metadata and its note thread. Never its content: a person's tasks are not the
 * back-office's business, and the API would not serve them anyway.
 */
export function UserDrawer({ user, onClose }: UserDrawerProps) {
    const { message } = App.useApp()
    const queryClient = useQueryClient()
    const [body, setBody] = useState('')

    const comments = useQuery({
        queryKey: ['user-comments', user.id],
        queryFn: () => api.fetchUserComments(user.id),
    })

    const addComment = useMutation({
        mutationFn: (note: string) => api.createUserComment(user.id, note),
        onSuccess: async () => {
            setBody('')
            await queryClient.invalidateQueries({ queryKey: ['user-comments', user.id] })
        },
        onError: (failure) => {
            message.error(
                failure instanceof ApiError && failure.violations !== null
                    ? summarise(failure.violations)
                    : 'La note n’a pas été ajoutée.',
            )
        },
    })

    return (
        <Drawer open width={520} onClose={onClose} title={user.username}>
            <Descriptions column={1} size="small" items={[
                { key: 'email', label: 'Adresse', children: user.email },
                {
                    key: 'status',
                    label: 'Statut',
                    children: (
                        <Tag color={user.isActive ? 'green' : 'red'}>
                            {user.isActive ? 'Actif' : 'Désactivé'}
                        </Tag>
                    ),
                },
                { key: 'role', label: 'Rôle', children: user.role },
                {
                    key: 'last',
                    label: 'Dernière connexion',
                    children: user.lastSignedInAt === null ? 'Jamais' : formatDate(user.lastSignedInAt),
                },
                {
                    key: 'created',
                    label: 'Inscription',
                    children: user.createdAt === null ? '—' : formatDate(user.createdAt),
                },
            ]} />

            <Divider>Notes internes</Divider>

            <Typography.Paragraph type="secondary" style={{ fontSize: 12 }}>
                Visibles ici seulement. Le compte concerné ne les voit jamais.
            </Typography.Paragraph>

            <Input.TextArea
                rows={3}
                value={body}
                placeholder="Ce qu’il faudra se rappeler"
                onChange={(event) => setBody(event.target.value)}
            />

            <Flex justify="center" style={{ margin: '12px 0 24px' }}>
                <Button
                    type="primary"
                    loading={addComment.isPending}
                    disabled={body.trim() === ''}
                    onClick={() => addComment.mutate(body)}
                >
                    Ajouter
                </Button>
            </Flex>

            <List
                loading={comments.isPending}
                dataSource={comments.data ?? []}
                locale={{ emptyText: <Empty description="Aucune note" /> }}
                renderItem={(comment) => (
                    <List.Item>
                        <List.Item.Meta
                            title={
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                                    {comment.authorUsername}
                                    {comment.createdAt !== null && ` · ${formatDate(comment.createdAt)}`}
                                </Typography.Text>
                            }
                            description={<Typography.Text>{comment.body}</Typography.Text>}
                        />
                    </List.Item>
                )}
            />
        </Drawer>
    )
}
