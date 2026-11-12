import { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import Header from '../components/Header.jsx';
import Footer from '../components/Footer.jsx';
import '../styles/GroupSession.css';

async function request(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Une erreur est survenue.');
    return data;
}

export default function GroupSession() {
    const { code } = useParams();
    const [session, setSession] = useState(null);
    const [nickname, setNickname] = useState('');
    const [city, setCity] = useState('');
    const [name, setName] = useState('');
    const [likedCuisines, setLikedCuisines] = useState('');
    const [recommendations, setRecommendations] = useState([]);
    const [participantId, setParticipantId] = useState(
        () => localStorage.getItem(`foodspot-participant-${code}`) || ''
    );
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [joinCode, setJoinCode] = useState('');

    useEffect(() => {
        if (code) {
            const refresh = () => request(`/api/group-sessions/${code}`)
                .then(setSession)
                .catch((requestError) => setError(requestError.message));
            refresh();
            const interval = window.setInterval(refresh, 10000);
            return () => window.clearInterval(interval);
        }
    }, [code]);

    async function createSession(event) {
        event.preventDefault();
        try {
            const data = await request('/api/group-sessions', {
                method: 'POST',
                body: JSON.stringify({ name, city, nickname }),
            });
            window.location.href = `/group/${data.code}`;
        } catch (requestError) {
            setError(requestError.message);
        }
    }

    async function joinSession(event) {
        event.preventDefault();
        try {
            const participant = await request(`/api/group-sessions/${code}/participants`, {
                method: 'POST',
                body: JSON.stringify({ nickname }),
            });
            localStorage.setItem(`foodspot-participant-${code}`, participant.id);
            setParticipantId(participant.id);
            setSession(await request(`/api/group-sessions/${code}`));
            setMessage('Vous avez rejoint la session.');
        } catch (requestError) {
            setError(requestError.message);
        }
    }

    async function savePreferences(event) {
        event.preventDefault();
        try {
            await request(`/api/group-sessions/${code}/preferences`, {
                method: 'PUT',
                body: JSON.stringify({
                    participantId,
                    preferences: {
                        likedCuisines: likedCuisines.split(',').map((item) => item.trim()).filter(Boolean),
                    },
                }),
            });
            setMessage('Préférences enregistrées.');
        } catch (requestError) {
            setError(requestError.message);
        }
    }

    async function loadRecommendations() {
        try {
            const data = await request(`/api/group-sessions/${code}/recommendations`);
            setRecommendations(data.recommendations);
        } catch (requestError) {
            setError(requestError.message);
        }
    }

    async function vote(restaurantId) {
        try {
            await request(`/api/group-sessions/${code}/votes`, {
                method: 'POST',
                body: JSON.stringify({ participantId, restaurantId }),
            });
            setMessage('Vote enregistré.');
        } catch (requestError) {
            setError(requestError.message);
        }
    }

    return (
        <div className="group-page">
            <Header />
            <main className="group-main">
                {!code ? (
                    <section className="group-panel">
                        <span className="eyebrow">DISCOVERY TOGETHER</span>
                        <h1>Find a place everyone can enjoy.</h1>
                        <form onSubmit={createSession} className="group-form">
                            <input required placeholder="Nom de la session" value={name} onChange={(event) => setName(event.target.value)} />
                            <input required placeholder="Ville" value={city} onChange={(event) => setCity(event.target.value)} />
                            <input required placeholder="Votre pseudo" value={nickname} onChange={(event) => setNickname(event.target.value)} />
                            <button type="submit">Créer la session</button>
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
                        <p>{session.city} · {session.participants.length} participant(s)</p>
                        {!participantId && (
                            <form onSubmit={joinSession} className="group-form">
                                <input required placeholder="Votre pseudo" value={nickname} onChange={(event) => setNickname(event.target.value)} />
                                <button type="submit">Rejoindre</button>
                            </form>
                        )}
                        {participantId && (
                            <>
                                <form onSubmit={savePreferences} className="group-form">
                                    <input placeholder="Cuisines aimées, séparées par des virgules" value={likedCuisines} onChange={(event) => setLikedCuisines(event.target.value)} />
                                    <button type="submit">Enregistrer mes préférences</button>
                                </form>
                                <button type="button" onClick={loadRecommendations}>Voir les recommandations</button>
                                <div className="recommendations">
                                    {recommendations.map((restaurant) => (
                                        <article key={restaurant.id}>
                                            <strong>{restaurant.name}</strong>
                                            <span>{restaurant.category || 'Restaurant'}</span>
                                            <p>{restaurant.reason}</p>
                                            <button type="button" onClick={() => vote(restaurant.id)}>Voter pour ce lieu</button>
                                        </article>
                                    ))}
                                </div>
                            </>
                        )}
                    </section>
                ) : (
                    <section className="group-panel"><p>Chargement de la session…</p></section>
                )}
                {message && <p className="group-message">{message}</p>}
                {error && <p className="group-error">{error}</p>}
            </main>
            <Footer />
        </div>
    );
}
