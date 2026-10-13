import { useQuery } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import type { CSSProperties, FormEvent } from "react";
import { Link, useParams, useSearchParams } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  getSessionHistory,
  getTestHistory,
  getTestResult,
  getTestReview,
  ResultsApiError,
} from "./results-api.js";

function errorMessage(error: unknown): string {
  return error instanceof ResultsApiError
    ? error.message
    : "Não foi possível carregar os resultados.";
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat("pt-BR", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value));
}

function formatDuration(totalSeconds: number, includeSeconds = true): string {
  const hours = Math.floor(totalSeconds / 3600) % 24;
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;
  const base = `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
  return includeSeconds ? `${base}:${String(seconds).padStart(2, "0")}` : base;
}

function phpNotEmpty(value: string | null): boolean {
  return value !== null && value !== "" && value !== "0";
}

function LoadingError({
  pending,
  error,
  children,
}: {
  pending: boolean;
  error: unknown;
  children: React.ReactNode;
}) {
  if (pending) {
    return <p role="status">Carregando...</p>;
  }
  if (error) {
    return (
      <p className="notice notice--error" role="alert">
        {errorMessage(error)} {" · "} <Link to="/historico/provas">Voltar ao histórico</Link>
      </p>
    );
  }
  return children;
}

export function TestResultPage() {
  const { id = "" } = useParams();
  const query = useQuery({
    queryKey: ["test-result", id],
    queryFn: () => getTestResult(id),
  });
  const result = query.data;

  return (
    <>
      <PublicHeader />
      <main className="results-page content-width">
        <LoadingError pending={query.isPending} error={query.error}>
          {result && (
            <section
              className="result-summary"
              style={{ "--result-color": result.cor } as CSSProperties}
            >
              <header>
                <h1>🎉 Resultado do Teste</h1>
                <h2>{result.nome}</h2>
                <p className="result-summary__score">{result.pontuacao.toFixed(1)}%</p>
              </header>
              <div className="results-stats">
                <article>
                  <h3>Questões acertadas</h3>
                  <p>{result.acertos}</p>
                </article>
                <article>
                  <h3>Questões respondidas</h3>
                  <p>{result.questoes_respondidas}</p>
                </article>
                <article>
                  <h3>Tempo gasto</h3>
                  <p>{formatDuration(result.tempo_gasto)}</p>
                </article>
              </div>
              <nav className="button-row" aria-label="Ações do resultado">
                <Link
                  className="button button--primary"
                  to={`/resultados/${result.sessao_id}/revisao`}
                >
                  🔍 Revisar prova
                </Link>
                <Link className="button button--secondary" to="/historico/provas">
                  📋 Histórico
                </Link>
                <Link className="button button--primary" to="/simulador">
                  🔄 Novo teste
                </Link>
              </nav>
            </section>
          )}
        </LoadingError>
      </main>
    </>
  );
}

export function TestReviewPage() {
  const { id = "" } = useParams();
  const query = useQuery({
    queryKey: ["test-review", id],
    queryFn: () => getTestReview(id),
  });
  const review = query.data;

  return (
    <>
      <PublicHeader />
      <main className="results-page content-width">
        <LoadingError pending={query.isPending} error={query.error}>
          {review && (
            <>
              <header className="results-heading">
                <h1>🔍 Revisão da Prova</h1>
                <p>
                  {review.session.nome} · {formatDate(review.session.inicio)}
                </p>
              </header>
              <section
                className="results-stats review-summary"
                style={{ "--result-color": review.session.cor } as CSSProperties}
                aria-label="Resumo da prova"
              >
                <article>
                  <h2>Pontuação final</h2>
                  <p>{review.session.pontuacao.toFixed(1)}%</p>
                </article>
                <article>
                  <h2>Questões corretas</h2>
                  <p>{review.session.acertos}</p>
                </article>
                <article>
                  <h2>Questões respondidas</h2>
                  <p>{review.session.questoes_respondidas}</p>
                </article>
                <article>
                  <h2>Tempo gasto</h2>
                  <p>{formatDuration(review.session.tempo_gasto)}</p>
                </article>
              </section>

              {review.questions.length === 0 ? (
                <section className="results-empty">
                  <h2>Nenhuma questão disponível para revisão</h2>
                </section>
              ) : (
                <div className="review-layout">
                  <section className="review-questions" aria-label="Questões da prova">
                    {review.questions.map((question) => {
                      const answered =
                        phpNotEmpty(question.resposta_usuario) ||
                        phpNotEmpty(question.resposta_dissertativa_usuario);
                      const isCorrect = question.esta_correta === true;
                      const status = !answered
                        ? "Não Respondida"
                        : isCorrect
                          ? "Correta"
                          : "Incorreta";
                      const statusClass = !answered
                        ? "review-status--empty"
                        : isCorrect
                          ? "review-status--correct"
                          : "review-status--wrong";
                      const isEssay = question.tipo_questao === "dissertativa";
                      const expectedAnswer = phpNotEmpty(question.resposta_dissertativa)
                        ? question.resposta_dissertativa
                        : (question.resposta_correta ?? "");

                      return (
                        <article
                          className="review-question"
                          id={`questao-${question.numero_questao}`}
                          key={question.id}
                        >
                          <header>
                            <h2>
                              Questão {question.numero_questao}
                              {question.assunto ? ` - ${question.assunto}` : ""}
                            </h2>
                            <span className={`review-status ${statusClass}`}>{status}</span>
                          </header>
                          <p className="review-question__prompt">{question.enunciado}</p>
                          {isEssay ? (
                            <div className="review-essay">
                              <h3>Sua resposta:</h3>
                              <p>
                                {phpNotEmpty(question.resposta_dissertativa_usuario) ? (
                                  question.resposta_dissertativa_usuario
                                ) : (
                                  <em>Questão não respondida</em>
                                )}
                              </p>
                              <h3>Resposta correta:</h3>
                              <p>{expectedAnswer}</p>
                            </div>
                          ) : (
                            <div className="review-options">
                              {Object.entries(question.alternativas).map(([letter, text]) => {
                                const selected = question.resposta_usuario === letter;
                                const correct = question.resposta_correta === letter;
                                const className = [
                                  "review-option",
                                  selected ? "review-option--selected" : "",
                                  correct ? "review-option--correct" : "",
                                  selected && !isCorrect ? "review-option--wrong" : "",
                                ]
                                  .filter(Boolean)
                                  .join(" ");
                                return (
                                  <div className={className} key={letter}>
                                    <strong>{letter.toUpperCase()})</strong>
                                    <span>{text}</span>
                                    {correct && <small>✅ Resposta correta</small>}
                                    {selected && !isCorrect && (
                                      <small>❌ Sua resposta (incorreta)</small>
                                    )}
                                  </div>
                                );
                              })}
                            </div>
                          )}
                        </article>
                      );
                    })}
                  </section>
                  <nav className="review-nav" aria-label="Navegação das questões">
                    <h2>Questões</h2>
                    {review.questions.map((question) => {
                      const answered =
                        phpNotEmpty(question.resposta_usuario) ||
                        phpNotEmpty(question.resposta_dissertativa_usuario);
                      const status = !answered
                        ? "review-nav__item--empty"
                        : question.esta_correta
                          ? "review-nav__item--correct"
                          : "review-nav__item--wrong";
                      return (
                        <a
                          className={`review-nav__item ${status}`}
                          href={`#questao-${question.numero_questao}`}
                          key={question.id}
                        >
                          {question.numero_questao}
                        </a>
                      );
                    })}
                  </nav>
                </div>
              )}
              <nav className="button-row results-actions">
                <Link className="button button--secondary" to="/historico/provas">
                  📋 Voltar ao histórico
                </Link>
                <Link className="button button--primary" to="/simulador">
                  🔄 Nova prova
                </Link>
              </nav>
            </>
          )}
        </LoadingError>
      </main>
    </>
  );
}

const testTypeOptions = [
  ["toefl", "TOEFL"],
  ["ielts", "IELTS"],
  ["sat", "SAT"],
  ["dele", "DELE"],
  ["delf", "DELF"],
  ["testdaf", "TestDaF"],
  ["jlpt", "JLPT"],
  ["hsk", "HSK"],
];

function performance(score: number) {
  if (score >= 90) return { label: "Excelente", color: "#4CAF50", icon: "🏆" };
  if (score >= 80) return { label: "Muito Bom", color: "#2196F3", icon: "🥇" };
  if (score >= 70) return { label: "Bom", color: "#FF9800", icon: "🥈" };
  if (score >= 60) return { label: "Satisfatório", color: "#FFC107", icon: "🥉" };
  return { label: "Precisa Melhorar", color: "#F44336", icon: "📚" };
}

export function TestHistoryPage() {
  const query = useQuery({ queryKey: ["test-history"], queryFn: getTestHistory });
  const [searchParams, setSearchParams] = useSearchParams();
  const [type, setType] = useState(searchParams.get("tipo") ?? "");
  const [period, setPeriod] = useState(searchParams.get("periodo") ?? "");

  useEffect(() => {
    setType(searchParams.get("tipo") ?? "");
    setPeriod(searchParams.get("periodo") ?? "");
  }, [searchParams]);

  function applyFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const params = new URLSearchParams();
    if (type) params.set("tipo", type);
    if (period) params.set("periodo", period);
    setSearchParams(params);
  }

  return (
    <>
      <PublicHeader />
      <main className="results-page content-width">
        <header className="results-heading">
          <h1>📊 Histórico de Testes</h1>
          <p>Acompanhe seu progresso e evolução.</p>
        </header>
        <LoadingError pending={query.isPending} error={query.error}>
          {query.data && (
            <>
              <section className="results-stats" aria-label="Estatísticas gerais">
                <article>
                  <h2>Total de testes</h2>
                  <p>{query.data.stats.total_testes}</p>
                  <small>realizados</small>
                </article>
                <article>
                  <h2>Média geral</h2>
                  <p>{query.data.stats.media_geral.toFixed(1)}%</p>
                  <small>pontuação média</small>
                </article>
                <article>
                  <h2>Melhor resultado</h2>
                  <p>{query.data.stats.melhor_pontuacao.toFixed(1)}%</p>
                  <small>maior pontuação</small>
                </article>
                <article>
                  <h2>Taxa de aprovação</h2>
                  <p>
                    {query.data.stats.total_testes === 0
                      ? "0.0"
                      : (
                          (query.data.stats.testes_aprovados / query.data.stats.total_testes) *
                          100
                        ).toFixed(1)}
                    %
                  </p>
                  <small>≥ 70% de acerto</small>
                </article>
              </section>

              {query.data.by_type.length > 0 && (
                <section className="history-by-type">
                  <h2>📈 Desempenho por Tipo de Prova</h2>
                  <div className="history-type-grid">
                    {query.data.by_type.map((entry) => (
                      <article
                        className="history-type-card"
                        key={entry.tipo_prova}
                        style={{ "--result-color": entry.cor } as CSSProperties}
                      >
                        <span aria-hidden="true">{entry.icone}</span>
                        <h3>{entry.nome}</h3>
                        <p>{entry.media_pontuacao.toFixed(1)}%</p>
                        <small>{entry.testes_por_tipo} testes realizados</small>
                      </article>
                    ))}
                  </div>
                </section>
              )}

              <div className="history-columns">
                <section className="history-list">
                  <h2>📝 Últimos Testes</h2>
                  <form className="history-filters" onSubmit={applyFilters}>
                    <label>
                      Tipo de Prova:
                      <select value={type} onChange={(event) => setType(event.target.value)}>
                        <option value="">Todos os tipos</option>
                        {testTypeOptions.map(([value, label]) => (
                          <option key={value} value={value}>
                            {label}
                          </option>
                        ))}
                      </select>
                    </label>
                    <label>
                      Período:
                      <select value={period} onChange={(event) => setPeriod(event.target.value)}>
                        <option value="">Todos os períodos</option>
                        <option value="7">Últimos 7 dias</option>
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 3 meses</option>
                      </select>
                    </label>
                    <button className="button button--primary" type="submit">
                      🔍 Filtrar
                    </button>
                  </form>
                  {query.data.items.length === 0 ? (
                    <div className="results-empty">
                      <span aria-hidden="true">📝</span>
                      <h3>Nenhum teste realizado ainda</h3>
                      <p>Comece fazendo seu primeiro simulado!</p>
                      <Link className="button button--primary" to="/simulador">
                        Fazer teste
                      </Link>
                    </div>
                  ) : (
                    <div className="test-history-list">
                      {query.data.items.map((item) => {
                        const level = performance(item.pontuacao);
                        return (
                          <Link
                            className="test-history-item"
                            key={item.sessao_id}
                            to={`/resultados/${item.sessao_id}`}
                            style={{ "--result-color": item.cor } as CSSProperties}
                          >
                            <span className="test-history-item__icon" aria-hidden="true">
                              {item.icone}
                            </span>
                            <span className="test-history-item__details">
                              <strong>{item.nome}</strong>
                              <small>{formatDate(item.data_realizacao)}</small>
                              <small>
                                {item.acertos}/{item.total_questoes} acertos ·{" "}
                                {formatDuration(item.tempo_gasto)}
                              </small>
                            </span>
                            <span className="test-history-item__score">
                              <strong>{item.pontuacao.toFixed(1)}%</strong>
                              <small style={{ backgroundColor: level.color }}>
                                {level.icon} {level.label}
                              </small>
                            </span>
                          </Link>
                        );
                      })}
                    </div>
                  )}
                </section>
                <aside className="results-empty history-badges">
                  <h2>🏆 Suas Conquistas</h2>
                  <p>Conquistas e badges não estão disponíveis nesta versão.</p>
                </aside>
              </div>
              <p className="history-note">
                Os filtros de tipo e período aparecem no legado, mas não filtram os resultados.
              </p>
            </>
          )}
        </LoadingError>
      </main>
    </>
  );
}

export function SessionHistoryPage() {
  const query = useQuery({ queryKey: ["session-history"], queryFn: getSessionHistory });
  const history = query.data;

  return (
    <>
      <PublicHeader />
      <main className="results-page content-width">
        <header className="results-heading">
          <h1>📊 Histórico de Provas</h1>
          <p>Acompanhe seu progresso e desempenho.</p>
        </header>
        <LoadingError pending={query.isPending} error={query.error}>
          {history &&
            (history.items.length === 0 ? (
              <section className="results-empty">
                <span aria-hidden="true">📝</span>
                <h2>Nenhuma prova realizada ainda</h2>
                <p>Comece fazendo seu primeiro simulado!</p>
                <Link className="button button--primary" to="/simulador">
                  🚀 Fazer primeira prova
                </Link>
              </section>
            ) : (
              <>
                <section className="results-stats" aria-label="Estatísticas de provas">
                  <article>
                    <h2>Provas realizadas</h2>
                    <p>{history.stats.total_provas}</p>
                  </article>
                  <article>
                    <h2>Questões respondidas</h2>
                    <p>{history.stats.questoes_respondidas}</p>
                  </article>
                  <article>
                    <h2>Tempo total de estudo</h2>
                    <p>{formatDuration(history.stats.tempo_total, false)}</p>
                  </article>
                  <article>
                    <h2>Média de pontuação</h2>
                    <p>{history.stats.media_pontuacao.toFixed(1)}%</p>
                  </article>
                </section>
                <section className="session-history">
                  <h2>📋 Histórico Detalhado de Provas</h2>
                  <div className="session-history__scroll">
                    <table>
                      <thead>
                        <tr>
                          <th>Prova</th>
                          <th>Data/Hora</th>
                          <th>Questões</th>
                          <th>Acertos</th>
                          <th>Pontuação</th>
                          <th>Tempo</th>
                          <th>Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        {history.items.map((item) => {
                          const scoreClass =
                            item.pontuacao_calculada >= 80
                              ? "session-score--excellent"
                              : item.pontuacao_calculada >= 60
                                ? "session-score--good"
                                : "session-score--regular";
                          return (
                            <tr key={item.sessao_id}>
                              <td>
                                <span
                                  className="session-exam-badge"
                                  style={{ backgroundColor: item.cor }}
                                >
                                  {item.nome}
                                </span>
                              </td>
                              <td>{formatDate(item.inicio)}</td>
                              <td>
                                <strong>{item.questoes_respondidas}</strong>
                                {item.total_questoes > 0 && ` / ${item.total_questoes}`}
                              </td>
                              <td>
                                <strong>{item.acertos}</strong>
                              </td>
                              <td>
                                <strong className={scoreClass}>
                                  {item.pontuacao_calculada.toFixed(1)}%
                                </strong>
                              </td>
                              <td>{formatDuration(item.tempo_gasto)}</td>
                              <td>
                                <Link
                                  className="button button--primary"
                                  to={`/resultados/${item.sessao_id}/revisao`}
                                >
                                  🔍 Revisar
                                </Link>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                </section>
              </>
            ))}
        </LoadingError>
        <nav className="button-row results-actions">
          <Link className="button button--primary" to="/simulador">
            🔄 Nova prova
          </Link>
          <Link className="button button--secondary" to="/historico/testes">
            📊 Histórico de testes
          </Link>
        </nav>
      </main>
    </>
  );
}
