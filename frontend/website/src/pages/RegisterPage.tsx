import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiError } from '../api/client'
import { register } from '../api/endpoints'
import { useAuth } from '../auth/useAuth'
import {
    Alert,
    AuthShell,
    Button,
    EmailField,
    Form,
    FormActions,
    PasswordField,
    SystemPanel,
    TextField,
} from '../components'

export function RegisterPage() {
    const { signIn } = useAuth()
    const navigate = useNavigate()
    const [username, setUsername] = useState('')
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [error, setError] = useState<ApiError | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async () => {
        setPending(true)
        setError(null)

        try {
            await register(username, email, password)
            // Signing up and then asking for credentials again would be rude.
            await signIn(email, password)
            void navigate('/')
        } catch (failure) {
            setError(failure instanceof ApiError ? failure : new ApiError(0))
        } finally {
            setPending(false)
        }
    }

    return (
        <AuthShell>
            <SystemPanel titleInside title="Éveil">
                <p className="system-text dim" style={{ marginTop: 0 }}>
                    Tu as été désigné. Crée ton profil.
                </p>

                <Form onSubmit={submit}>
                    <TextField
                        label="Nom"
                        isMandatory
                        errors={error?.violationsFor('username')}
                        value={username}
                        onChange={setUsername}
                        autoComplete="username"
                        autoFocus
                    />

                    <EmailField label="Adresse" isMandatory errors={error?.violationsFor('email')} value={email} onChange={setEmail} />

                    <PasswordField
                        label="Mot de passe"
                        isMandatory
                        errors={error?.violationsFor('password')}
                        value={password}
                        onChange={setPassword}
                        autoComplete="new-password"
                    />

                    <p className="system-text dim" style={{ fontSize: 12 }}>
                        Huit caractères, une majuscule, une minuscule, un chiffre, un caractère spécial.
                    </p>

                    {error !== null && error.violations === null && (
                        <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
                    )}

                    <FormActions>
                        <Button variant="primary" submit disabled={pending}>
                            {pending ? 'S’éveiller…' : 'S’éveiller'}
                        </Button>
                    </FormActions>
                </Form>

                <p className="system-text dim" style={{ marginBottom: 0 }}>
                    Déjà un compte ? <Link to="/connexion">Se connecter</Link>
                </p>
            </SystemPanel>
        </AuthShell>
    )
}
