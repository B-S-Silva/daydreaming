import { useQuery } from "@tanstack/react-query";
import { useEffect, useRef, useState } from "react";
import { Link } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  getUniversityById,
  getUniversityRanking,
  getUniversityRankingFilters,
  type UniversityRankingQuery,
} from "./universityRankingApi.js";
import "./university-ranking.css";

const regionLabels: Record<string, string> = {
  americas: "Américas",
  europe: "Europa",
  asia: "Ásia",
  africa: "África",
  oceania: "Oceania",
};

const pageSize = 10;

function countryFlag(code: string): string {
  return String.fromCodePoint(
    ...code
      .toUpperCase()
      .split("")
      .map((letter) => 127397 + letter.charCodeAt(0)),
  );
}

export function UniversityRankingPage() {
  const [draftCountry, setDraftCountry] = useState("");
  const [draftRegion, setDraftRegion] = useState("");
  const [country, setCountry] = useState("");
  const [region, setRegion] = useState("");
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState<NonNullable<UniversityRankingQuery["sortBy"]>>("rank");
  const [orderBy, setOrderBy] = useState<NonNullable<UniversityRankingQuery["orderBy"]>>("asc");
  const [page, setPage] = useState(1);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const detailsRef = useRef<HTMLElement>(null);

  const filtersQuery = useQuery({
    queryKey: ["university-ranking-filters"],
    queryFn: getUniversityRankingFilters,
  });
  const rankingQuery = useQuery({
    queryKey: ["university-ranking", country, region, search, sortBy, orderBy, page],
    queryFn: () =>
      getUniversityRanking({ country, region, search, sortBy, orderBy, page, pageSize }),
    placeholderData: (previousData) => previousData,
  });
  const detailsQuery = useQuery({
    queryKey: ["university-ranking-detail", selectedId],
    queryFn: () => getUniversityById(selectedId ?? ""),
    enabled: selectedId !== null,
  });

  useEffect(() => {
    if (selectedId && detailsQuery.data) {
      detailsRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }, [selectedId, detailsQuery.data]);

  useEffect(() => {
    if (!selectedId) {
      return;
    }
    function closeOnEscape(event: KeyboardEvent) {
      if (event.key === "Escape") {
        setSelectedId(null);
      }
    }
    window.addEventListener("keydown", closeOnEscape);
    return () => window.removeEventListener("keydown", closeOnEscape);
  }, [selectedId]);

  function applyFilters() {
    setCountry(draftCountry);
    setRegion(draftRegion);
    setPage(1);
  }

  function clearFilters() {
    setDraftCountry("");
    setDraftRegion("");
    setCountry("");
    setRegion("");
    setSearch("");
    setSortBy("rank");
    setOrderBy("asc");
    setPage(1);
  }

  const totalPages = rankingQuery.data
    ? Math.ceil(rankingQuery.data.total / rankingQuery.data.pageSize)
    : 0;

  return (
    <>
      <PublicHeader />
      <main className="university-ranking">
        <div className="university-ranking__content">
          <header className="university-ranking__heading">
            <p className="university-ranking__eyebrow">DayDreaming · Planejamento acadêmico</p>
            <h1>Ranking Mundial de Universidades</h1>
            <p>Descubra instituições de ensino superior e compare os indicadores do ranking.</p>
            <p className="university-ranking__data-note">
              Dados migrados da versão anterior; a fonte e a edição do ranking não estão
              identificadas. Confirme posições e indicadores diretamente nas universidades e na
              publicação oficial antes de tomar decisões.
            </p>
          </header>

          <section className="university-ranking__filters" aria-label="Filtros do ranking">
            <h2>Filtrar resultados</h2>
            <div className="university-ranking__filter-grid">
              <label>
                <span>País</span>
                <select
                  aria-label="País"
                  value={draftCountry}
                  onChange={(event) => {
                    setDraftCountry(event.target.value);
                  }}
                >
                  <option value="">Todos os países</option>
                  {filtersQuery.data?.countries.map(({ code, name }) => (
                    <option key={code} value={code}>
                      {name}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                <span>Região</span>
                <select
                  aria-label="Região"
                  value={draftRegion}
                  onChange={(event) => {
                    setDraftRegion(event.target.value);
                  }}
                >
                  <option value="">Todas as regiões</option>
                  {filtersQuery.data?.regions.map((value) => (
                    <option key={value} value={value}>
                      {regionLabels[value] ?? value}
                    </option>
                  ))}
                </select>
              </label>
              <label className="university-ranking__search">
                <span>Buscar universidade</span>
                <input
                  aria-label="Buscar universidade"
                  type="search"
                  placeholder="Digite o nome da universidade"
                  value={search}
                  onChange={(event) => {
                    setSearch(event.target.value);
                    setCountry(draftCountry);
                    setRegion(draftRegion);
                    setPage(1);
                  }}
                />
              </label>
            </div>
            <div className="university-ranking__filter-actions">
              <p>Área de estudo indisponível: o ranking de origem não fornece esse dado.</p>
              <button
                className="university-ranking__button university-ranking__button--subtle"
                type="button"
                onClick={clearFilters}
              >
                Limpar filtros
              </button>
              <button
                className="university-ranking__button"
                type="button"
                onClick={applyFilters}
              >
                Aplicar filtros
              </button>
            </div>
          </section>

          <section
            className="university-ranking__results"
            aria-labelledby="university-ranking-results"
          >
            <div className="university-ranking__results-heading">
              <div>
                <h2 id="university-ranking-results">Resultados</h2>
                {rankingQuery.data && (
                  <p>
                    {rankingQuery.data.total}{" "}
                    {rankingQuery.data.total === 1 ? "universidade" : "universidades"}
                  </p>
                )}
              </div>
              <div className="university-ranking__sorting">
                <label>
                  <span>Ordenar por</span>
                  <select
                    aria-label="Ordenar por"
                    value={sortBy}
                    onChange={(event) => {
                      setSortBy(
                        event.target.value as NonNullable<UniversityRankingQuery["sortBy"]>,
                      );
                      setPage(1);
                    }}
                  >
                    <option value="rank">Ranking</option>
                    <option value="name">Nome</option>
                    <option value="score">Pontuação</option>
                  </select>
                </label>
                <label>
                  <span>Direção</span>
                  <select
                    aria-label="Direção da ordenação"
                    value={orderBy}
                    onChange={(event) => {
                      setOrderBy(
                        event.target.value as NonNullable<UniversityRankingQuery["orderBy"]>,
                      );
                      setPage(1);
                    }}
                  >
                    <option value="asc">Crescente</option>
                    <option value="desc">Decrescente</option>
                  </select>
                </label>
              </div>
            </div>

            {filtersQuery.isPending && (
              <p className="university-ranking__notice" role="status">
                Carregando filtros...
              </p>
            )}
            {filtersQuery.error && (
              <p
                className="university-ranking__notice university-ranking__notice--error"
                role="alert"
              >
                {filtersQuery.error.message}
              </p>
            )}
            {rankingQuery.isPending && (
              <p className="university-ranking__notice" role="status">
                Carregando ranking...
              </p>
            )}
            {rankingQuery.error && (
              <p
                className="university-ranking__notice university-ranking__notice--error"
                role="alert"
              >
                {rankingQuery.error.message}
              </p>
            )}
            {rankingQuery.data?.total === 0 && (
              <p className="university-ranking__notice" role="status">
                Nenhuma universidade corresponde aos filtros.
              </p>
            )}
            {rankingQuery.data && rankingQuery.data.items.length > 0 && (
              <>
                <div className="university-ranking__table-wrap">
                  <table>
                    <thead>
                      <tr>
                        <th scope="col">Ranking</th>
                        <th scope="col">Universidade</th>
                        <th scope="col">País</th>
                        <th scope="col">Pontuação</th>
                        <th scope="col">Detalhes</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rankingQuery.data.items.map((university) => (
                        <tr key={university.id}>
                          <td className="university-ranking__rank">#{university.rank}</td>
                          <td>
                            <div className="university-ranking__university">
                              <img
                                src={university.logo}
                                alt=""
                                loading="lazy"
                                onError={(event) => {
                                  event.currentTarget.hidden = true;
                                }}
                              />
                              <span>
                                {university.name}
                                <small className="university-ranking__country-subtitle">
                                  {university.country}
                                </small>
                              </span>
                            </div>
                          </td>
                          <td>
                            <span className="university-ranking__flag" aria-hidden="true">
                              {countryFlag(university.countryCode)}
                            </span>{" "}
                            {university.country}
                          </td>
                          <td>{university.score.toFixed(1)}</td>
                          <td>
                            <button
                              className="university-ranking__button"
                              type="button"
                              onClick={() => setSelectedId(university.id)}
                            >
                              Ver detalhes
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {totalPages > 1 && (
                  <nav className="university-ranking__pagination" aria-label="Paginação do ranking">
                    <button
                      className="university-ranking__button university-ranking__button--subtle"
                      type="button"
                      disabled={page <= 1}
                      onClick={() => setPage((current) => Math.max(1, current - 1))}
                    >
                      Anterior
                    </button>
                    <div className="university-ranking__page-numbers">
                      {Array.from({ length: totalPages }, (_, index) => index + 1).map(
                        (pageNumber) => (
                          <button
                            aria-current={pageNumber === page ? "page" : undefined}
                            aria-label={`Página ${pageNumber}`}
                            className={
                              pageNumber === page
                                ? "university-ranking__page-number is-active"
                                : "university-ranking__page-number"
                            }
                            key={pageNumber}
                            onClick={() => setPage(pageNumber)}
                            type="button"
                          >
                            {pageNumber}
                          </button>
                        ),
                      )}
                    </div>
                    <span aria-live="polite">
                      Página {page} de {totalPages}
                    </span>
                    <button
                      className="university-ranking__button university-ranking__button--subtle"
                      type="button"
                      disabled={page >= totalPages}
                      onClick={() => setPage((current) => Math.min(totalPages, current + 1))}
                    >
                      Próxima
                    </button>
                  </nav>
                )}
              </>
            )}
          </section>

          {selectedId && (
            <section
              ref={detailsRef}
              className="university-ranking__details"
              aria-labelledby="university-ranking-detail-title"
              role="dialog"
              aria-modal="true"
            >
              <div className="university-ranking__details-heading">
                {detailsQuery.data && (
                  <img
                    alt={detailsQuery.data.name}
                    className="university-ranking__details-logo"
                    loading="lazy"
                    onError={(event) => {
                      event.currentTarget.hidden = true;
                    }}
                    src={detailsQuery.data.logo}
                  />
                )}
                <div>
                  {detailsQuery.data && <p>{detailsQuery.data.location}</p>}
                  <h2 id="university-ranking-detail-title">
                    {detailsQuery.data?.name ?? "Detalhes da universidade"}
                  </h2>
                </div>
                <button
                  className="university-ranking__button university-ranking__button--subtle"
                  type="button"
                  onClick={() => setSelectedId(null)}
                  aria-label="Fechar detalhes"
                >
                  Fechar
                </button>
              </div>
              {detailsQuery.isPending && (
                <p className="university-ranking__notice" role="status">
                  Carregando detalhes...
                </p>
              )}
              {detailsQuery.error && (
                <p
                  className="university-ranking__notice university-ranking__notice--error"
                  role="alert"
                >
                  {detailsQuery.error.message}
                </p>
              )}
              {detailsQuery.data && (
                <div className="university-ranking__detail-grid">
                  {detailsQuery.data.details.map((detail) => (
                    <article key={detail.title}>
                      <h3>{detail.title}</h3>
                      <strong>{detail.value}</strong>
                      <p>{detail.description}</p>
                    </article>
                  ))}
                </div>
              )}
            </section>
          )}

          <footer className="university-ranking__back">
            <Link className="university-ranking__button" to="/">
              Voltar à página inicial
            </Link>
          </footer>
        </div>
      </main>
    </>
  );
}
