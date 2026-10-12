import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Link } from "react-router";
import type { Continent } from "@daydreaming/shared";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import { getInternationalExamCatalog } from "./internationalExamsApi.js";

const continentOptions: { id: Continent; label: string }[] = [
  { id: "america", label: "🌎 América" },
  { id: "europa", label: "🇪🇺 Europa" },
  { id: "asia", label: "🌏 Ásia" },
  { id: "oceania", label: "🇦🇺 Oceania" },
  { id: "africa", label: "🌍 África" },
];

const continentNames: Record<Continent, string> = {
  america: "América do Norte e Sul",
  europa: "Europa",
  asia: "Ásia",
  oceania: "Oceania",
  africa: "África",
};

function openSimulator() {
  window.alert("Você precisa estar logado para prosseguir!");
  if (window.confirm("Deseja efetuar o login?")) {
    window.location.href = "/login.php";
  }
}

export function ExamCatalogPage() {
  const [selectedContinent, setSelectedContinent] = useState<Continent | "all">("all");
  const [selectedCountry, setSelectedCountry] = useState("all");
  const { data, error, isPending } = useQuery({
    queryKey: ["international-exams"],
    queryFn: getInternationalExamCatalog,
  });

  return (
    <>
      <PublicHeader />
      <main className="catalog-page">
        <div className="content-width">
          <header className="catalog-heading">
            <h1>Guia Completo de Testes Internacionais</h1>
            <p>
              Descubra os principais testes e provas exigidos para estudar no exterior. Prepare-se
              adequadamente para conquistar sua vaga em universidades internacionais.
            </p>
          </header>

          {isPending && (
            <p className="notice" role="status">
              Carregando catálogo...
            </p>
          )}
          {error && (
            <p className="notice notice--error" role="alert">
              {error.message}
            </p>
          )}
          {data && data.countries.length === 0 && data.exams.length === 0 && (
            <p className="notice" role="status">
              Nenhuma informação de exames está disponível no momento.
            </p>
          )}

          {data && (
            <>
              <section className="filter-panel" aria-label="Filtros de países">
                <h2>🌍 Filtrar por Continente</h2>
                <div className="filter-buttons">
                  <button
                    className="filter-button"
                    type="button"
                    aria-pressed={selectedContinent === "all"}
                    onClick={() => setSelectedContinent("all")}
                  >
                    Todos
                  </button>
                  {continentOptions.map(({ id, label }) => (
                    <button
                      className="filter-button"
                      key={id}
                      type="button"
                      aria-pressed={selectedContinent === id}
                      onClick={() => setSelectedContinent(id)}
                    >
                      {label}
                    </button>
                  ))}
                </div>
                <label className="country-select">
                  <span>🏛️ Filtrar por País</span>
                  <select
                    aria-label="Filtrar por país"
                    value={selectedCountry}
                    onChange={(event) => setSelectedCountry(event.target.value)}
                  >
                    <option value="all">Todos os Países</option>
                    {data.countries.map((country) => (
                      <option key={country.id} value={country.id}>
                        {country.flag} {country.name}
                      </option>
                    ))}
                  </select>
                </label>
              </section>

              {continentOptions
                .filter(({ id }) => selectedContinent === "all" || selectedContinent === id)
                .map(({ id }) => {
                  const countries = data.countries.filter(
                    (country) =>
                      country.continent === id &&
                      (selectedCountry === "all" || country.id === selectedCountry),
                  );

                  return (
                    <section className="continent-section" key={id}>
                      <h2>{continentNames[id]}</h2>
                      <div className="country-grid">
                        {countries.map((country) => (
                          <article className="country-card" key={country.id}>
                            <h3>
                              {country.flag} {country.name}
                            </h3>
                            <ul>
                              {country.requirements.map((requirement) => (
                                <li key={requirement}>{requirement}</li>
                              ))}
                            </ul>
                            <button
                              className="simulator-button"
                              type="button"
                              onClick={openSimulator}
                            >
                              Simular Teste
                            </button>
                          </article>
                        ))}
                      </div>
                    </section>
                  );
                })}

              <section className="exam-section">
                <h2>Principais Testes</h2>
                <div className="exam-grid">
                  {data.exams.map((exam) => (
                    <article className="exam-card" key={exam.id}>
                      <h3>{exam.name}</h3>
                      <p className="exam-card__subtitle">{exam.subtitle}</p>
                      <div className="exam-detail">
                        <strong>Para quem é:</strong>
                        <p>{exam.audience}</p>
                      </div>
                      <div className="exam-detail">
                        <strong>Formato:</strong>
                        <p>{exam.format}</p>
                      </div>
                      <div className="exam-detail">
                        <strong>{exam.noteLabel}:</strong>
                        <p>{exam.note}</p>
                      </div>
                    </article>
                  ))}
                </div>
              </section>

              {selectedCountry === "all" && data.regionalGuides.length > 0 && (
                <section className="regional-section">
                  <h2>Testes por País/Região</h2>
                  <div className="country-grid">
                    {data.regionalGuides.map((guide) => (
                      <article className="country-card" key={guide.id}>
                        <h3>{guide.country}</h3>
                        <ul>
                          {guide.requirements.map((requirement) => (
                            <li key={requirement}>{requirement}</li>
                          ))}
                        </ul>
                        <button className="simulator-button" type="button" onClick={openSimulator}>
                          {guide.id === "uk" || guide.id === "australia"
                            ? "Simulador IELTS"
                            : "Simular Teste"}
                        </button>
                      </article>
                    ))}
                  </div>
                </section>
              )}

              <section className="tips-section">
                <h2>Dicas Importantes para Preparação</h2>
                <ul>
                  {data.tips.map((tip) => (
                    <li key={tip.title}>
                      <strong>{tip.title}:</strong> {tip.description}
                    </li>
                  ))}
                </ul>
              </section>
            </>
          )}

          <div className="catalog-back">
            <Link className="button button--primary" to="/">
              Voltar à Página Inicial
            </Link>
          </div>
        </div>
      </main>
    </>
  );
}
