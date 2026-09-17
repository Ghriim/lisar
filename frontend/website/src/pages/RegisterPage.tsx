import { useState, type FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiError } from '../api/client'
import { register } from '../api/endpoints'
import { useAuth } from '../auth/useAuth'
import { Field, TextInput } from '../components/Field'
import { FormActions } from '../components/FormActions'
import { SystemPanel } from '../components/SystemPanel'

export function RegisterPage() {
    const { signIn } = useAuth()
    const navigate = useNavigate()
    const [username, setUsername] = useState('')
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [error, setError] = useState<ApiError | null>(null)
    const [pending, setPending] = useState(false)

    const submit = async (event: FormEvent) => {
        event.preventDefault()
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
        <div className="auth-screen">
            <SystemPanel title="Éveil" className="panel auth-panel">
                <p className="system-text dim" style={{ marginTop: 0 }}>
                    Tu as été désigné. Crée ton profil.
                </p>

                <form className="form-grid" onSubmit={(event) => void submit(event)}>
                    <Field label="Nom" errors={error?.violationsFor('username')}>
                        <TextInput
                            value={username}
                            onChange={(event) => setUsername(event.target.value)}
                            autoComplete="username"
                            required
                            autoFocus
                        />
                    </Field>

                    <Field label="Adresse" errors={error?.violationsFor('email')}>
                        <TextInput
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            autoComplete="email"
                            required
                        />
                    </Field>

                    <Field label="Mot de passe" errors={error?.violationsFor('password')}>
                        <TextInput
                            type="password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            autoComplete="new-password"
                            required
                        />
                    </Field>

                    <p className="system-text dim" style={{ fontSize: 10 }}>
                        Huit caractères, une majuscule, une minuscule, un chiffre, un caractère spécial.
                    </p>

                    {error !== null && error.violations === null && (
                        <p className="alert">Le System ne répond pas. Réessaie dans un instant.</p>
                    )}

                    <FormActions>
                        <button type="submit" className="button" disabled={pending}>
                            {pending ? 'S’éveiller…' : 'S’éveiller'}
                        </button>
                    </FormActions>
                </form>

                <p className="system-text dim" style={{ marginBottom: 0 }}>
                    Déjà un compte ? <Link to="/connexion">Se connecter</Link>
                </p>
            </SystemPanel>
        </div>
    )
}
