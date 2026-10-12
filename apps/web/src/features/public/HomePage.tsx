import { Link } from "react-router";

const features = [
  {
    icon: "🎯",
    title: "Testes Personalizados",
    description:
      "Simulados específicos para cada país e tipo de exame, adaptados ao seu nível e objetivos acadêmicos.",
    href: "/simulador_provas.php",
  },
  {
    icon: "🌍",
    title: "Cobertura Global",
    description:
      "Informações sobre testes de proficiência para mais de 15 países em 5 continentes diferentes.",
    href: "/exames-internacionais",
  },
  {
    icon: "📊",
    title: "Acompanhamento Detalhado",
    description:
      "Monitore seu progresso com relatórios detalhados e dicas personalizadas para melhorar seu desempenho.",
    href: "/simulador_provas.php#ancora",
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
    href: "/forum.php",
  },
];

function PublicHeader() {
  return (
    <header className="public-header">
      <div className="public-header__identity">
        <span className="public-header__status">❌ Você não está logado</span>
        <Link className="public-header__home" to="/">
          🏠 Página Inicial
        </Link>
      </div>
      <a className="public-header__login" href="/login.php">
        🔑 Fazer Login
      </a>
    </header>
  );
}

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
              <a className="button button--outline" href="/login.php">
                👤 Fazer Login
              </a>
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
              <strong>15+</strong>
              <span>Países Disponíveis</span>
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
              <a className="button button--primary" href="/cadastro.php">
                ✨ Criar Conta Grátis
              </a>
              <Link className="button button--secondary" to="/exames-internacionais">
                🔍 Ver Todos os Testes
              </Link>
            </div>
          </div>
        </section>
      </main>
      <footer className="site-footer">
        <p>© 2024 StudyAbroad. Transformando sonhos em realidade. ✈️🎓</p>
      </footer>
    </>
  );
}
