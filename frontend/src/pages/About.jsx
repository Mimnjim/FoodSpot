import Header from '../components/Header.jsx';
import Footer from '../components/Footer.jsx';
import '../styles/About.css';

export default function About() {
    return (
        <div className="about-page">
            <Header />
            <main className="about-main">
                <span className="eyebrow">À PROPOS</span>
                <h1>Choisir un restaurant devient plus simple à plusieurs.</h1>
                <p>
                    FoodSpot rassemble les restaurants disponibles autour de vous
                    et aide les groupes à trouver un lieu qui convient à tout le monde.
                </p>
                <p>
                    Les données de lieux externes sont fournies par OpenStreetMap
                    et restent affichées uniquement lorsqu'elles sont disponibles.
                </p>
            </main>
            <Footer />
        </div>
    );
}
