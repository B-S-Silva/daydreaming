import { Link } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";

const features = [
  {
    icon: "🎯",
    title: "Testes Personalizados",
    description:
      "Simulados específicos para cada país e tipo de exame, adaptados ao seu nível e objetivos acadêmicos.",
    href: "/simulador",
  },
  {
    icon: "🌍",
    title: "Cobertura Global",
    description: "Informações sobre testes de proficiência e preparação internacional.",
    href: "/exames-internacionais",
  },
  {
    icon: "🧭",
    title: "Guias por País",
    description: "Explore guias de candidatura, bolsas e planejamento para 28 destinos de estudo.",
    href: "/guias-paises",
  },
  {
    icon: "📚",
    title: "Ferramentas de Estudo",
    description:
      "Estime seu GPA, explore interesses profissionais e pratique respostas para entrevistas.",
    href: "/ferramentas-estudo",
  },
  {
    icon: "🏛️",
    title: "Ranking Universitário",
    description: "Compare 89 universidades por país, região, posição e pontuação.",
    href: "/ranking",
  },
  {
    icon: "📊",
    title: "Acompanhamento Detalhado",
    description:
      "Monitore seu progresso com relatórios detalhados e dicas personalizadas para melhorar seu desempenho.",
    href: "/simulador",
  },
  {
    icon: "🎓",
    title: "Preparação Completa",
    description: "Informações específicas SAT, MEXT, GMAT, GRE e muito mais.",
  },
  {
    icon: "⚡",
    title: "Resultados Rápidos",
    description: "Feedback imediato nos simulados com explicações detalhadas para cada questão.",
  },
  {
    icon: "💡",
    title: "Dicas de Especialistas",
    description:
      "Conteúdo criado por especialistas em educação internacional e ex-estudantes no exterior.",
    href: "/forum",
  },
];

export function HomePage() {
  return (
    <>
      <PublicHeader />
      <main>
        <section className="hero">
          <div className="hero__ornaments" aria-hidden="true">
            <span>🎓</span>
            <span>✈️</span>
            <span>🌍</span>
          </div>
          <div className="hero__content">
            <h1>🌟 Realize Seu Sonho de Estudar no Exterior</h1>
            <p>
              Descubra as melhores oportunidades de estudo internacional e prepare-se para os testes
              de proficiência com nossa plataforma completa
            </p>
            <div className="button-row">
              <Link className="button button--light" to="/exames-internacionais">
                🚀 Explorar Testes
              </Link>
              <Link className="button button--outline" to="/login">
                👤 Fazer Login
              </Link>
            </div>
          </div>
        </section>

        <section className="features page-section">
          <div className="content-width">
            <div className="feature-grid">
              {features.map((feature) => {
                const content = (
                  <>
                    <span className="feature-card__icon" aria-hidden="true">
                      {feature.icon}
                    </span>
                    <h2>{feature.title}</h2>
                    <p>{feature.description}</p>
                  </>
                );

                return feature.href ? (
                  <a className="feature-card" href={feature.href} key={feature.title}>
                    {content}
                  </a>
                ) : (
                  <article className="feature-card" key={feature.title}>
                    {content}
                  </article>
                );
              })}
            </div>
          </div>
        </section>

        <section className="stats page-section">
          <div className="content-width stats-grid">
            <div>
              <strong>28</strong>
              <span>Guias de Países</span>
            </div>
            <div>
              <strong>30+</strong>
              <span>Tipos de Exames</span>
            </div>
            <div>
              <strong>1000+</strong>
              <span>Questões Práticas</span>
            </div>
          </div>
        </section>

        <section className="callout page-section">
          <div className="callout__content">
            <h2>🚀 Comece Sua Jornada Hoje</h2>
            <p>
              Junte-se a milhares de estudantes que já realizaram o sonho de estudar no exterior.
              Sua aventura internacional começa aqui!
            </p>
            <div className="button-row">
              <Link className="button button--primary" to="/cadastro">
                ✨ Criar Conta Grátis
              </Link>
              <Link className="button button--secondary" to="/exames-internacionais">
                🔍 Ver Todos os Testes
              </Link>
            </div>
          </div>
        </section>
      </main>
      <footer className="site-footer">
        <p>DayDreaming — planejamento e preparação para estudar no exterior.</p>
        <Link to="/quem-somos">Quem somos</Link>
      </footer>
    </>
  );
}
