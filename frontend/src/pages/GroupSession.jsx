import { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import Header from '../components/Header.jsx';
import Footer from '../components/Footer.jsx';
import { apiRequest } from '../api/foodSpotApi.js';
import '../styles/GroupSession.css';

export default function GroupSession() {
    const { code } = useParams();
    const [session, setSession] = useState(null);
    const [nickname, setNickname] = useState('');
    const [city, setCity] = useState('');
    const [name, setName] = useState('');
    const [likedCuisines, setLikedCuisines] = useState([]);
    const [avoidedCuisines, setAvoidedCuisines] = useState([]);
    const [restrictions, setRestrictions] = useState([]);
    const [budget, setBudget] = useState('');
    const [recommendations, setRecommendations] = useState([]);
    const [participantId, setParticipantId] = useState(
        () => localStorage.getItem(`foodspot-participant-${code}`) || ''
    );
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [joinCode, setJoinCode] = useState('');
    const [result, setResult] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const cuisineChoices = ['Français', 'Italien', 'Japonais', 'Asiatique', 'Libanais', 'Mexicain', 'Végétarien'];
    const restrictionChoices = ['Sans gluten', 'Végétarien', 'Végétalien', 'Halal', 'Sans lactose'];

    function toggleChoice(choice, values, setValues) {
        setValues(values.includes(choice)
            ? values.filter((value) => value !== choice)
            : [...values, choice]);
    }

    useEffect(() => {
        if (code) {
            const refresh = () => {
                setIsRefreshing(true);
                return apiRequest(`/api/group-sessions/${code}`)
                    .then(setSession)
                    .catch((requestError) => setError(requestError.message))
                    .finally(() => setIsRefreshing(false));
            };
            refresh();
            const interval = window.setInterval(refresh, 10000);
            return () => window.clearInterval(interval);
        }
    }, [code]);

    async function createSession(event) {
        event.preventDefault();
        setIsSubmitting(true);
        try {
            const data = await apiRequest('/api/group-sessions', {
                method: 'POST',
                body: JSON.stringify({ name, city, nickname }),
            });
            localStorage.setItem(
                `foodspot-participant-${data.code}`,
                data.participants[0].id
            );
            window.location.href = `/group/${data.code}`;
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    async function joinSession(event) {
        event.preventDefault();
        setIsSubmitting(true);
        try {
            const participant = await apiRequest(`/api/group-sessions/${code}/participants`, {
                method: 'POST',
                body: JSON.stringify({ nickname }),
            });
            localStorage.setItem(`foodspot-participant-${code}`, participant.id);
            setParticipantId(participant.id);
            setSession(await apiRequest(`/api/group-sessions/${code}`));
            setMessage('Vous avez rejoint la session.');
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    async function savePreferences(event) {
        event.preventDefault();
        setIsSubmitting(true);
        try {
            await apiRequest(`/api/group-sessions/${code}/preferences`, {
                method: 'PUT',
                body: JSON.stringify({
                    participantId,
                    preferences: {
                        likedCuisines,
                        avoidedCuisines,
                        restrictions,
                        budget: budget || null,
                    },
                }),
            });
            setMessage('Préférences enregistrées.');
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }

    }

    async function leaveSession() {
        setIsSubmitting(true);
        try {
            await apiRequest(`/api/group-sessions/${code}/participants/${participantId}`, { method: 'DELETE' });
            localStorage.removeItem(`foodspot-participant-${code}`);
            window.location.href = '/group';
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    async function loadRecommendations() {
        setIsSubmitting(true);
        try {
            const data = await apiRequest(`/api/group-sessions/${code}/recommendations`);
            setRecommendations(data.recommendations);
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }

    }

    async function vote(restaurantId) {
        setIsSubmitting(true);
        try {
            await apiRequest(`/api/group-sessions/${code}/votes`, {
                method: 'POST',
                body: JSON.stringify({ participantId, restaurantId }),
            });
            setMessage('Vote enregistré.');
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }

    }

    async function decide(event) {
        const mode = event.currentTarget.dataset.mode;
        setIsSubmitting(true);
        try {
            const data = await apiRequest(`/api/group-sessions/${code}/decision`, {
                method: 'POST',
                body: JSON.stringify({ participantId, mode }),
            });
            setResult(data.result);
            setSession(await apiRequest(`/api/group-sessions/${code}`));
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setIsSubmitting(false);
        }
    }

    function hasVoted(participant) {
        return Boolean(session?.votes?.[participant.id]);
    }

    return (
        <div className="group-page">
            <Header />
            <main className="group-main">
                {!code ? (
                    <section className="group-panel">
                        <span className="eyebrow">DÉCOUVERTE À PLUSIEURS</span>
                        <h1>Trouvez un lieu qui convient à tout le monde.</h1>
                        <form onSubmit={createSession} className="group-form">
                            <input required placeholder="Nom de la session" value={name} onChange={(event) => setName(event.target.value)} />
                            <input required placeholder="Ville" value={city} onChange={(event) => setCity(event.target.value)} />
                            <input required placeholder="Votre pseudo" value={nickname} onChange={(event) => setNickname(event.target.value)} />
                            <button type="submit" disabled={isSubmitting}>{isSubmitting ? 'Création…' : 'Créer la session'}</button>
                        </form>
                        <p className="group-or">Vous avez un code ?</p>
                        <form className="group-form" onSubmit={(event) => {
                            event.preventDefault();
                            window.location.href = `/group/${joinCode.trim().toUpperCase()}`;
                        }}>
                            <input required placeholder="Code de session" value={joinCode} onChange={(event) => setJoinCode(event.target.value)} />
                            <button type="submit">Rejoindre une session</button>
                        </form>
                    </section>
                ) : session ? (
                    <section className="group-panel">
                        <span className="eyebrow">SESSION {session.code}</span>
                        <h1>{session.name}</h1>
                        <p>{session.city} · {session.participants.length} participant(s) · {Object.keys(session.preferences || {}).length} réponse(s)</p>
                        <div className="participant-list" aria-label="Participants">
                            {session.participants.map((participant) => (
                                <span key={participant.id} className={hasVoted(participant) ? 'participant is-ready' : 'participant'}>
                                    {participant.nickname}{hasVoted(participant) ? ' ✓' : ''}
                                </span>
                            ))}
                        </div>
                        {session.status !== 'collecting' ? (
                            <>
                                <p className="group-status">
                                    {session.status === 'expired'
                                        ? 'Cette session a expiré.'
                                        : 'La décision finale a déjà été prise.'}
                                </p>
                                {session.result && (
                                    <div className="group-result">
                                        <span>DECISION FINALE</span>
                                        <a href={`/restaurant/${session.result.restaurantId}`} className="group-result-link">
                                            {session.result.restaurantName || session.result.restaurantId}
                                        </a>
                                        {session.result.restaurant?.address && <p>{session.result.restaurant.address}</p>}
                                        <p>Choix effectué avec le mode {session.result.mode}.</p>
                                    </div>
                                )}
                            </>
                        ) : !participantId && (
                            <form onSubmit={joinSession} className="group-form">
                                <input required placeholder="Votre pseudo" value={nickname} onChange={(event) => setNickname(event.target.value)} />
                                <button type="submit" disabled={isSubmitting}>{isSubmitting ? 'Connexion…' : 'Rejoindre'}</button>
                            </form>
                        )}
                        {participantId && session.status === 'collecting' && (
                            <>
                                <form onSubmit={savePreferences} className="group-form">
                                    <fieldset className="preference-fieldset">
                                        <legend>Qu'est-ce que vous aimez ?</legend>
                                        <div className="preference-options">
                                            {cuisineChoices.map((choice) => (
                                                <button
                                                    type="button"
                                                    key={choice}
                                                    className={likedCuisines.includes(choice) ? 'active' : ''}
                                                    onClick={() => toggleChoice(choice, likedCuisines, setLikedCuisines)}
                                                >
                                                    {choice}
                                                </button>
                                            ))}
                                        </div>
                                    </fieldset>
                                    <fieldset className="preference-fieldset">
                                        <legend>Qu'est-ce que vous évitez ?</legend>
                                        <div className="preference-options">
                                            {cuisineChoices.map((choice) => (
                                                <button
                                                    type="button"
                                                    key={choice}
                                                    className={avoidedCuisines.includes(choice) ? 'active' : ''}
                                                    onClick={() => toggleChoice(choice, avoidedCuisines, setAvoidedCuisines)}
                                                >
                                                    {choice}
                                                </button>
                                            ))}
                                        </div>
                                    </fieldset>
                                    <fieldset className="preference-fieldset">
                                        <legend>Restrictions alimentaires</legend>
                                        <div className="preference-options">
                                            {restrictionChoices.map((choice) => (
                                                <button
                                                    type="button"
                                                    key={choice}
                                                    className={restrictions.includes(choice) ? 'active' : ''}
                                                    onClick={() => toggleChoice(choice, restrictions, setRestrictions)}
                                                >
                                                    {choice}
                                                </button>
                                            ))}
                                        </div>
                                    </fieldset>
                                    <select aria-label="Budget" value={budget} onChange={(event) => setBudget(event.target.value)}>
                                        <option value="">Budget non renseigné</option>
                                        <option value="1">€</option>
                                        <option value="2">€€</option>
                                        <option value="3">€€€</option>
                                        <option value="4">€€€€</option>
                                    </select>
                                    <button type="submit" disabled={isSubmitting}>Enregistrer mes préférences</button>
                                </form>
                                <button type="button" onClick={leaveSession} disabled={isSubmitting}>Quitter la session</button>
                                <button type="button" onClick={loadRecommendations} disabled={isSubmitting}>Voir les recommandations</button>
                                <div className="recommendations">
                                    {recommendations.map((restaurant) => (
                                        <article key={restaurant.id}>
                                            <strong>{restaurant.name}</strong>
                                            <span>{restaurant.category || 'Restaurant'}</span>
                                            <p>{restaurant.reason}</p>
                                            <button type="button" onClick={() => vote(restaurant.id)} disabled={isSubmitting}>Voter pour ce lieu</button>
                                        </article>
                                    ))}
                                </div>
                                {participantId === session.creatorParticipantId && session.votes && Object.keys(session.votes).length > 0 && (
                                    <div className="decision-actions">
                                        <button type="button" data-mode="auto" onClick={decide} disabled={isSubmitting}>Décider automatiquement</button>
                                        <button type="button" data-mode="roulette" onClick={decide} disabled={isSubmitting}>Lancer la roulette</button>
                                    </div>
                                )}
                                {(result || session.result) && (
                                    <div className="group-result">
                                        <span>DECISION FINALE</span>
                                        <a href={`/restaurant/${(result || session.result).restaurantId}`} className="group-result-link">
                                            {(result || session.result).restaurantName || (result || session.result).restaurantId}
                                        </a>
                                        {(result || session.result).restaurant?.address && <p>{(result || session.result).restaurant.address}</p>}
                                        <p>Choix effectué avec le mode {(result || session.result).mode}.</p>
                                    </div>
                                )}
                            </>
                        )}
                    </section>
                ) : (
                    <section className="group-panel"><p>Chargement de la session…</p></section>
                )}
                {isRefreshing && <p className="group-sync">Synchronisation…</p>}
                {message && <p className="group-message">{message}</p>}
                {error && <p className="group-error">{error}</p>}
            </main>
            <Footer />
        </div>
    );
}
