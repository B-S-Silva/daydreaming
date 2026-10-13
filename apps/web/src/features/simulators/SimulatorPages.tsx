import { useMutation, useQuery } from "@tanstack/react-query";
import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router";
import type { CSSProperties } from "react";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  finishSimulator,
  getSimulatorDashboard,
  getSimulatorSession,
  saveSimulatorAnswer,
  startSimulator,
  SimulatorApiError,
} from "./simulator-api.js";

function errorMessage(error: unknown): string {
  return error instanceof SimulatorApiError
    ? error.message
    : "Não foi possível carregar o simulador.";
}

function formatDuration(totalSeconds: number): string {
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(totalSeconds % 60).padStart(2, "0")}`;
}

function formatExamDuration(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const remainder = minutes % 60;
  return `${hours}h${remainder > 0 ? `${String(remainder).padStart(2, "0")}m` : ""}`;
}

export function SimulatorCatalogPage() {
  const dashboardQuery = useQuery({
    queryKey: ["simulator-dashboard"],
    queryFn: getSimulatorDashboard,
  });
  const dashboard = dashboardQuery.data;

  return (
    <>
      <PublicHeader />
      <main className="simulator-page content-width">
        <header className="simulator-heading">
          <h1>📝 Simulador de Provas</h1>
          <p>Escolha uma prova para praticar e acompanhar seu desempenho.</p>
        </header>
        {dashboardQuery.isPending && <p role="status">Carregando simulados...</p>}
        {dashboardQuery.error && (
          <p className="notice notice--error" role="alert">
            {errorMessage(dashboardQuery.error)}
            {" · "}
            <Link to="/login">Faça login</Link>
          </p>
        )}
        {dashboard && (
          <>
            <section className="simulator-stats" aria-label="Estatísticas do usuário">
              <article>
                <h2>Total de testes</h2>
                <p>{dashboard.stats.total_testes}</p>
              </article>
              <article>
                <h2>Média de pontuação</h2>
                <p>
                  {dashboard.stats.media_pontuacao === null
                    ? "—"
                    : `${dashboard.stats.media_pontuacao.toFixed(1)}%`}
                </p>
              </article>
            </section>
            <section className="simulator-grid" aria-label="Provas disponíveis">
              {dashboard.exams.map((exam) => (
                <article
                  className="simulator-card"
                  key={exam.tipo_prova}
                  style={{ "--simulator-color": exam.cor } as CSSProperties}
                >
                  <div className="simulator-card__title">
                    <span aria-hidden="true">{exam.icone}</span>
                    <div>
                      <h2>{exam.nome}</h2>
                      <p>{exam.descricao}</p>
                    </div>
                  </div>
                  <dl>
                    <div>
                      <dt>Duração</dt>
                      <dd>{exam.duracao}</dd>
                    </div>
                    <div>
                      <dt>Questões</dt>
                      <dd>{exam.questoes}</dd>
                    </div>
                    <div>
                      <dt>Dificuldade</dt>
                      <dd>Média</dd>
                    </div>
                  </dl>
                  <Link className="button button--primary" to={`/simulador/${exam.tipo_prova}`}>
                    Iniciar simulado
                  </Link>
                </article>
              ))}
            </section>
          </>
        )}
      </main>
    </>
  );
}

export function SimulatorOverviewPage() {
  const { tipo } = useParams();
  const navigate = useNavigate();
  const dashboardQuery = useQuery({
    queryKey: ["simulator-dashboard"],
    queryFn: getSimulatorDashboard,
  });
  const startMutation = useMutation({
    mutationFn: () => startSimulator(tipo ?? ""),
    onSuccess: (result) => navigate(`/simulador/sessoes/${result.sessao_id}`),
  });
  const exam = dashboardQuery.data?.exams.find((item) => item.tipo_prova === tipo);

  return (
    <>
      <PublicHeader />
      <main className="simulator-page content-width simulator-overview">
        {dashboardQuery.isPending && <p role="status">Carregando prova...</p>}
        {dashboardQuery.error && (
          <p className="notice notice--error" role="alert">
            {errorMessage(dashboardQuery.error)}
            {" · "}
            <Link to="/login">Faça login</Link>
          </p>
        )}
        {!dashboardQuery.isPending && !dashboardQuery.error && !exam && (
          <section className="simulator-panel">
            <h1>Tipo de prova inválido</h1>
            <Link to="/simulador">Voltar ao simulador</Link>
          </section>
        )}
        {exam && (
          <section
            className="simulator-panel"
            style={{ "--simulator-color": exam.cor } as CSSProperties}
          >
            <p className="simulator-panel__icon" aria-hidden="true">
              {exam.icone}
            </p>
            <h1>{exam.nome}</h1>
            <p>Prepare-se para o simulado</p>
            <dl>
              <div>
                <dt>Duração</dt>
                <dd>{formatExamDuration(exam.duracao_minutos)}</dd>
              </div>
              <div>
                <dt>Questões</dt>
                <dd>{exam.questoes_total}</dd>
              </div>
              <div>
                <dt>Tipo</dt>
                <dd>{exam.nome}</dd>
              </div>
            </dl>
            <div className="simulator-instructions">
              <h2>📋 Instruções importantes</h2>
              <ul>
                <li>Leia atentamente cada questão antes de responder.</li>
                <li>Você pode navegar entre as questões livremente.</li>
                <li>Suas respostas são salvas automaticamente.</li>
                <li>O cronômetro inicia assim que você começar.</li>
                <li>Não feche a aba do navegador durante o teste.</li>
              </ul>
            </div>
            <p className="simulator-warning">
              ⚠️ Uma vez iniciado, o teste não pode ser pausado. Certifique-se de que tem tempo
              suficiente para completá-lo.
            </p>
            {startMutation.error && (
              <p className="notice notice--error" role="alert">
                {errorMessage(startMutation.error)}
              </p>
            )}
            <div className="button-row">
              <button
                className="button button--primary"
                disabled={startMutation.isPending}
                onClick={() => startMutation.mutate()}
                type="button"
              >
                {startMutation.isPending ? "Iniciando..." : "🚀 Iniciar teste agora"}
              </button>
              <Link className="button button--secondary" to="/simulador">
                Voltar ao simulador
              </Link>
            </div>
          </section>
        )}
      </main>
    </>
  );
}

export function SimulatorSessionPage() {
  const { id = "" } = useParams();
  const query = useQuery({
    queryKey: ["simulator-session", id],
    queryFn: () => getSimulatorSession(id),
    refetchOnWindowFocus: false,
  });
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const answersRef = useRef(answers);
  const [secondsRemaining, setSecondsRemaining] = useState<number | null>(null);
  const [saveError, setSaveError] = useState<string | null>(null);
  const finishMutation = useMutation({ mutationFn: finishSimulator });
  const saveMutation = useMutation({
    mutationFn: saveSimulatorAnswer,
    onSuccess: () => setSaveError(null),
    onError: (error) => setSaveError(errorMessage(error)),
  });
  const session = query.data;
  const question = session?.questoes[currentQuestionIndex];
  const answeredCount = Object.keys(answers).length;

  useEffect(() => {
    answersRef.current = answers;
  }, [answers]);

  useEffect(() => {
    if (!session || session.status !== "ativo" || finishMutation.isSuccess) {
      return;
    }
    let remaining = session.tempo_restante;
    setSecondsRemaining(remaining);
    const timer = window.setInterval(() => {
      remaining -= 1;
      setSecondsRemaining(Math.max(remaining, 0));
      if (remaining === 300) {
        window.alert("⚠️ Atenção: Restam apenas 5 minutos!");
      }
      if (remaining <= 0) {
        window.clearInterval(timer);
        window.alert("⏰ Tempo esgotado! O teste será finalizado automaticamente.");
        finishMutation.mutate({
          sessao_id: session.sessao_id,
          finalizar: "1",
          respostas: answersRef.current,
        });
      }
    }, 1000);
    return () => window.clearInterval(timer);
  }, [session, finishMutation.isSuccess]);

  useEffect(() => {
    if (!session || session.status !== "ativo" || finishMutation.isSuccess) {
      return;
    }
    const preventClose = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = "Tem certeza que deseja sair? Seu progresso será perdido.";
    };
    window.addEventListener("beforeunload", preventClose);
    return () => window.removeEventListener("beforeunload", preventClose);
  }, [session, finishMutation.isSuccess]);

  function saveAnswer(
    questionNumber: number,
    answer: string,
    type: "multipla_escolha" | "dissertativa",
  ) {
    setAnswers((current) => ({ ...current, [questionNumber]: answer }));
    saveMutation.mutate({
      sessao_id: session!.sessao_id,
      questao_numero: questionNumber,
      resposta: answer,
      tipo_resposta: type,
    });
  }

  function finish(confirmed: boolean) {
    if (!confirmed || !session) {
      return;
    }
    finishMutation.mutate({
      sessao_id: session.sessao_id,
      finalizar: "1",
      respostas: answers,
    });
  }

  function confirmFinish() {
    const unanswered = (session?.questoes_total ?? 0) - answeredCount;
    let message = `Você respondeu ${answeredCount} de ${session?.questoes_total ?? 0} questões.`;
    if (unanswered > 0) {
      message += `\n\n${unanswered} questões não foram respondidas.`;
    }
    message += "\n\nDeseja finalizar o teste?";
    finish(window.confirm(message));
  }

  return (
    <>
      <PublicHeader />
      <main className="simulator-page content-width">
        {query.isPending && <p role="status">Carregando teste...</p>}
        {query.error && (
          <p className="notice notice--error" role="alert">
            {errorMessage(query.error)}
            {" · "}
            <Link to="/simulador">Voltar ao simulador</Link>
          </p>
        )}
        {session?.status === "expirado" && (
          <section className="simulator-panel">
            <h1>Tempo esgotado</h1>
            <p>Esta sessão expirou.</p>
            <Link className="button button--primary" to="/simulador">
              Voltar ao simulador
            </Link>
          </section>
        )}
        {session?.status === "ativo" && finishMutation.data && (
          <section className="simulator-panel simulator-result">
            <h1>✅ Teste finalizado com sucesso!</h1>
            <p>Questões respondidas: {finishMutation.data.questoes_respondidas}</p>
            <p>Acertos: {finishMutation.data.acertos}</p>
            <p>Pontuação: {finishMutation.data.pontuacao.toFixed(1)}%</p>
            <Link className="button button--primary" to={`/resultados/${session.sessao_id}`}>
              Ver resultado e revisão
            </Link>
            <Link className="button button--secondary" to="/historico/provas">
              Histórico de provas
            </Link>
            <Link className="button button--primary" to="/simulador">
              Voltar ao simulador
            </Link>
          </section>
        )}
        {session?.status === "ativo" && !finishMutation.data && session.questoes.length === 0 && (
          <section className="simulator-panel">
            <h1>🚧 Exame {session.nome} em preparação</h1>
            <p>Este exame está sendo preparado e receberá questões reais em breve.</p>
            <p>
              Atualmente, apenas o <strong>SAT</strong> está disponível com questões completas do
              Practice Test #4.
            </p>
            <Link className="button button--primary" to="/simulador">
              Voltar ao simulador
            </Link>
          </section>
        )}
        {session?.status === "ativo" && !finishMutation.data && session.questoes.length > 0 && (
          <section
            className="simulator-test"
            style={{ "--simulator-color": session.cor } as CSSProperties}
          >
            <aside className="simulator-test__sidebar">
              <div className="simulator-test__timer">
                <h1>{session.nome}</h1>
                <p aria-live="polite">
                  {formatDuration(secondsRemaining ?? session.tempo_restante)}
                </p>
              </div>
              <nav className="simulator-question-nav" aria-label="Navegação de questões">
                {session.questoes.map((item, index) => (
                  <button
                    aria-label={`Questão ${item.numero}${answers[item.numero] ? ", respondida" : ""}`}
                    aria-current={index === currentQuestionIndex ? "step" : undefined}
                    className={[
                      "simulator-question-nav__button",
                      index === currentQuestionIndex ? "is-current" : "",
                      answers[item.numero] ? "is-answered" : "",
                    ]
                      .filter(Boolean)
                      .join(" ")}
                    key={item.id}
                    onClick={() => setCurrentQuestionIndex(index)}
                    type="button"
                  >
                    {item.numero}
                  </button>
                ))}
              </nav>
            </aside>
            <div className="simulator-test__main">
              <header className="simulator-test__heading">
                <strong>
                  Questão {question?.numero} de {session.questoes.length}
                </strong>
                <span>{question?.assunto || question?.materia || "Questão Geral"}</span>
                <button
                  className="button button--danger"
                  disabled={finishMutation.isPending}
                  onClick={confirmFinish}
                  type="button"
                >
                  {finishMutation.isPending ? "Finalizando..." : "🏁 Finalizar teste"}
                </button>
              </header>
              <div className="simulator-test__body">
                {session.questoes.length < session.questoes_total && (
                  <p className="simulator-warning" role="status">
                    Atenção: Este exame tem apenas {session.questoes.length} questões disponíveis de{" "}
                    {session.questoes_total} esperadas. O teste continuará com as questões
                    disponíveis.
                  </p>
                )}
                {question && (
                  <>
                    <p className="simulator-question-type">
                      {question.tipo_questao === "dissertativa"
                        ? "✏️ Questão dissertativa"
                        : "🔘 Múltipla escolha"}
                    </p>
                    <div className="simulator-question-prompt">{question.enunciado}</div>
                    {question.tipo_questao === "dissertativa" ? (
                      <label className="simulator-answer-text">
                        <span>Digite sua resposta</span>
                        <textarea
                          maxLength={1000}
                          onChange={(event) => {
                            const answer = event.target.value.trim();
                            if (answer) {
                              saveAnswer(question.numero, answer, "dissertativa");
                            } else {
                              setAnswers((current) => {
                                const next = { ...current };
                                delete next[question.numero];
                                return next;
                              });
                            }
                          }}
                          placeholder="Digite sua resposta aqui..."
                          value={answers[question.numero] ?? ""}
                        />
                        <small>💡 Seja claro e objetivo. Máximo de 1000 caracteres.</small>
                      </label>
                    ) : (
                      <div className="simulator-options">
                        {Object.entries(question.alternativas ?? {}).map(([letter, text]) => (
                          <label
                            className={
                              answers[question.numero] === letter ? "is-selected" : undefined
                            }
                            key={letter}
                          >
                            <input
                              checked={answers[question.numero] === letter}
                              name={`answer-${question.numero}`}
                              onChange={() =>
                                saveAnswer(question.numero, letter, "multipla_escolha")
                              }
                              type="radio"
                              value={letter}
                            />
                            <strong>{letter.toUpperCase()})</strong>
                            <span>{text}</span>
                          </label>
                        ))}
                      </div>
                    )}
                  </>
                )}
                {saveError && (
                  <p className="notice notice--error" role="alert">
                    {saveError}
                  </p>
                )}
                {finishMutation.error && (
                  <p className="notice notice--error" role="alert">
                    {errorMessage(finishMutation.error)}
                  </p>
                )}
                <footer className="simulator-test__footer">
                  <button
                    className="button button--secondary"
                    disabled={currentQuestionIndex === 0}
                    onClick={() => setCurrentQuestionIndex((index) => index - 1)}
                    type="button"
                  >
                    ← Anterior
                  </button>
                  <span>
                    Questão {question?.numero} de {session.questoes.length}
                  </span>
                  <button
                    className="button button--secondary"
                    disabled={currentQuestionIndex >= session.questoes.length - 1}
                    onClick={() => setCurrentQuestionIndex((index) => index + 1)}
                    type="button"
                  >
                    Próxima →
                  </button>
                </footer>
              </div>
            </div>
          </section>
        )}
      </main>
    </>
  );
}
