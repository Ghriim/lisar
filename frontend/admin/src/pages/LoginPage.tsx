import { Alert, Button, Card, Flex, Form, Input, Typography } from 'antd'
import { useState } from 'react'
import { ApiError } from '../api/client'
import { NotAnAdministratorError } from '../api/errors'
import { useAuth } from '../auth/useAuth'

interface Credentials {
    email: string
    password: string
}

export function LoginPage() {
    const { signIn } = useAuth()
    const [error, setError] = useState<string | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async (credentials: Credentials) => {
        setPending(true)
        setError(null)

        try {
            await signIn(credentials.email, credentials.password)
        } catch (failure) {
            setError(messageFor(failure))
        } finally {
            setPending(false)
        }
    }

    return (
        <Flex align="center" justify="center" style={{ minHeight: '100vh', padding: 24 }}>
            <Card style={{ width: 380 }}>
                <Typography.Title level={4}>lisar · back-office</Typography.Title>

                <Form<Credentials> layout="vertical" onFinish={(values) => void submit(values)} requiredMark={false}>
                    <Form.Item label="Adresse" name="email" rules={[{ required: true, message: 'Requis' }]}>
                        <Input type="email" autoComplete="email" autoFocus />
                    </Form.Item>

                    <Form.Item label="Mot de passe" name="password" rules={[{ required: true, message: 'Requis' }]}>
                        <Input.Password autoComplete="current-password" />
                    </Form.Item>

                    {error !== null && <Alert type="error" message={error} style={{ marginBottom: 16 }} />}

                    <Flex justify="center">
                        <Button type="primary" htmlType="submit" loading={pending}>
                            Entrer
                        </Button>
                    </Flex>
                </Form>
            </Card>
        </Flex>
    )
}

function messageFor(failure: unknown): string {
    if (failure instanceof NotAnAdministratorError) {
        return 'Ce compte existe, mais il n’est pas administrateur.'
    }

    if (failure instanceof ApiError) {
        if (failure.code === 'account_deactivated') {
            return 'Ce compte est désactivé.'
        }

        if (failure.status === 401) {
            return 'Adresse ou mot de passe incorrect.'
        }
    }

    return 'Le serveur ne répond pas. Réessayez dans un instant.'
}
