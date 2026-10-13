import { Link } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";

export function AboutPage() {
  return (
    <>
      <PublicHeader />
      <main className="country-guides-page content-width">
        <header className="results-heading">
          <h1>Conheça o DayDreaming</h1>
          <p>
            Jovens apaixonados por democratizar o acesso à educação internacional.
          </p>
        </header>
        <section className="guide-section">
          <h2>🎯 Nossa missão</h2>
          <p>
            Transformar sonhos de intercâmbio em realidade para estudantes da rede pública.
          </p>
        </section>
        <div className="about-mission-grid">
          <section className="guide-section">
            <h2>Democratizar o acesso</h2>
            <p>
              Oferecemos ferramentas gratuitas e informações essenciais para que qualquer estudante
              brasileiro possa planejar seus estudos no exterior, independente de sua condição
              socioeconômica.
            </p>
          </section>
          <section className="guide-section">
            <h2>Construir comunidade</h2>
            <p>
              Criamos uma rede de apoio entre estudantes com objetivos similares, promovendo troca de
              experiências, dicas valiosas e parcerias de estudo.
            </p>
          </section>
          <section className="guide-section">
            <h2>Conectar ao mundo</h2>
            <p>
              Facilitamos o acesso a informações sobre universidades, bolsas de estudo e
              oportunidades educacionais em diversos países ao redor do mundo.
            </p>
          </section>
        </div>
        <section className="guide-section">
          <h2>Projeto em desenvolvimento contínuo</h2>
          <p>
            O DayDreaming é um projeto em constante evolução. Estamos sempre aprimorando nossas
            ferramentas e expandindo nosso conhecimento para melhor servir nossa comunidade.
          </p>
        </section>
        <nav className="button-row" aria-label="Explorar o DayDreaming">
          <Link className="button button--primary" to="/guias-paises">
            Explorar guias
          </Link>
          <Link className="button button--secondary" to="/ferramentas-estudo">
            Ver ferramentas
          </Link>
        </nav>
      </main>
    </>
  );
}
