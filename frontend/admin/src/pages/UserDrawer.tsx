import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { User } from '../api/types'
import {
    Button,
    DateText,
    DescriptionList,
    DetailDrawer,
    ItemList,
    Paragraph,
    Row,
    Separator,
    Stack,
    StatusTag,
    Text,
    TextArea,
    useNotifier,
} from '../components'

interface UserDrawerProps {
    user: User
    onClose: () => void
}

/**
 * An account's metadata and its note thread. Never its content: a person's tasks are not the
 * back-office's business, and the API would not serve them anyway.
 */
export function UserDrawer({ user, onClose }: UserDrawerProps) {
    const notify = useNotifier()
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
        onError: (failure) => notify.failure(failure, 'La note n’a pas été ajoutée.'),
    })

    return (
        <DetailDrawer title={user.username} onClose={onClose}>
            <DescriptionList
                items={[
                    { label: 'Adresse', value: user.email },
                    { label: 'Statut', value: <StatusTag isActive={user.isActive} /> },
                    { label: 'Rôle', value: user.role },
                    {
                        label: 'Dernière connexion',
                        value: <DateText value={user.lastSignedInAt} fallback="Jamais" />,
                    },
                    { label: 'Inscription', value: <DateText value={user.createdAt} /> },
                ]}
            />

            <Separator>Notes internes</Separator>

            <Paragraph muted>Visibles ici seulement. Le compte concerné ne les voit jamais.</Paragraph>

            <TextArea
                value={body}
                placeholder="Ce qu’il faudra se rappeler"
                onChange={setBody}
            />

            <Row justify="center" style={{ margin: '12px 0 24px' }}>
                <Button
                    variant="primary"
                    loading={addComment.isPending}
                    disabled={body.trim() === ''}
                    onClick={() => addComment.mutate(body)}
                >
                    Ajouter
                </Button>
            </Row>

            <ItemList
                items={comments.data ?? []}
                loading={comments.isPending}
                emptyText="Aucune note"
                renderItem={(comment) => (
                    <Stack gap={4}>
                        <Text muted size="small">
                            {comment.authorUsername}
                            {comment.createdAt !== null && ' · '}
                            {comment.createdAt !== null && <DateText value={comment.createdAt} />}
                        </Text>
                        <Text>{comment.body}</Text>
                    </Stack>
                )}
            />
        </DetailDrawer>
    )
}
