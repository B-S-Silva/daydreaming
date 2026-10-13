import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Link } from "react-router";
import { QuestionExamTypeSchema } from "@daydreaming/shared";
import { getCurrentUser } from "../auth/auth-api.js";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  downloadQuestionExample,
  getQuestionStats,
  importQuestions,
  QuestionsApiError,
} from "./questions-api.js";
import type { QuestionExamType } from "@daydreaming/shared";

const examOptions: { value: QuestionExamType; label: string }[] = [
  { value: "toefl", label: "TOEFL" },
  { value: "ielts", label: "IELTS" },
  { value: "sat", label: "SAT" },
  { value: "dele", label: "DELE" },
  { value: "delf", label: "DELF" },
  { value: "testdaf", label: "TestDaF" },
  { value: "jlpt", label: "JLPT" },
  { value: "hsk", label: "HSK" },
];

function displayDate(value: string | null): string {
  if (!value) {
    return "—";
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.valueOf()) ? value : parsed.toLocaleString("pt-BR");
}

export function QuestionAdminPage() {
  const [examType, setExamType] = useState<QuestionExamType>("toefl");
  const [file, setFile] = useState<File | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const queryClient = useQueryClient();
  const currentUserQuery = useQuery({
    queryKey: ["auth-me"],
    queryFn: getCurrentUser,
  });
  const statsQuery = useQuery({
    queryKey: ["admin-question-stats"],
    queryFn: getQuestionStats,
    enabled: currentUserQuery.data?.authenticated === true && currentUserQuery.data.user.isAdmin,
  });
  const importMutation = useMutation({
    mutationFn: importQuestions,
    onSuccess: async (result) => {
      setMessage(result.message);
      setFile(null);
      await queryClient.invalidateQueries({ queryKey: ["admin-question-stats"] });
    },
  });
  const exampleMutation = useMutation({ mutationFn: downloadQuestionExample });
  const currentUser = currentUserQuery.data;

  return (
    <>
      <PublicHeader />
      <main className="question-admin-page content-width">
        <header className="question-admin-heading">
          <h1>Administração de Questões</h1>
          <p>Importe questões de provas internacionais ou baixe um arquivo de exemplo.</p>
        </header>

        {currentUserQuery.isPending && (
          <p className="notice" role="status">
            Verificando acesso...
          </p>
        )}
        {currentUserQuery.error && (
          <p className="notice notice--error" role="alert">
            Não foi possível verificar a sessão.
          </p>
        )}
        {currentUser && !currentUser.authenticated && (
          <p className="notice notice--error" role="alert">
            Faça <Link to="/login">login</Link> para acessar esta página.
          </p>
        )}
        {currentUser?.authenticated && !currentUser.user.isAdmin && (
          <p className="notice notice--error" role="alert">
            Acesso restrito a administradores.
          </p>
        )}

        {currentUser?.authenticated && currentUser.user.isAdmin && (
          <>
            <section className="question-admin-panel" aria-labelledby="question-stats-heading">
              <h2 id="question-stats-heading">Questões cadastradas</h2>
              {statsQuery.isPending && <p role="status">Carregando estatísticas...</p>}
              {statsQuery.error && (
                <p className="notice notice--error" role="alert">
                  {statsQuery.error instanceof QuestionsApiError
                    ? statsQuery.error.message
                    : "Não foi possível carregar as estatísticas."}
                </p>
              )}
              {statsQuery.data && statsQuery.data.items.length === 0 && (
                <p role="status">Nenhuma questão cadastrada.</p>
              )}
              {statsQuery.data && statsQuery.data.items.length > 0 && (
                <>
                  <p>
                    Total de questões: <strong>{statsQuery.data.total}</strong>
                  </p>
                  <div className="question-stats-table-wrap">
                    <table className="question-stats-table">
                      <thead>
                        <tr>
                          <th scope="col">Prova</th>
                          <th scope="col">Questões</th>
                          <th scope="col">Última atualização</th>
                        </tr>
                      </thead>
                      <tbody>
                        {statsQuery.data.items.map((item) => (
                          <tr key={item.tipo_prova}>
                            <th scope="row">{item.tipo_prova.toUpperCase()}</th>
                            <td>{item.total}</td>
                            <td>{displayDate(item.ultima_atualizacao)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </>
              )}
            </section>

            <section className="question-admin-panel" aria-labelledby="question-import-heading">
              <h2 id="question-import-heading">Importar questões</h2>
              {message && (
                <p className="notice" role="status">
                  {message}
                </p>
              )}
              {importMutation.error && (
                <p className="notice notice--error" role="alert">
                  {importMutation.error instanceof QuestionsApiError
                    ? importMutation.error.message
                    : "Erro ao carregar questões do arquivo."}
                </p>
              )}
              <form
                className="question-import-form"
                onSubmit={(event) => {
                  event.preventDefault();
                  setMessage(null);
                  if (file) {
                    importMutation.mutate({ examType, file });
                  }
                }}
              >
                <label>
                  Tipo de prova
                  <select
                    value={examType}
                    onChange={(event) => {
                      const parsedType = QuestionExamTypeSchema.safeParse(event.target.value);
                      if (parsedType.success) {
                        setExamType(parsedType.data);
                      }
                    }}
                  >
                    {examOptions.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                </label>
                <label>
                  Arquivo JSON ou XML (máximo 10 MB)
                  <input
                    accept=".json,.xml,application/json,text/xml,application/xml"
                    onChange={(event) => setFile(event.currentTarget.files?.[0] ?? null)}
                    required
                    type="file"
                  />
                </label>
                <button
                  className="button button--primary"
                  disabled={!file || importMutation.isPending}
                  type="submit"
                >
                  {importMutation.isPending ? "IMPORTANDO..." : "IMPORTAR QUESTÕES"}
                </button>
              </form>
              <div className="question-example-links" aria-label="Arquivos de exemplo">
                <h3>Baixar exemplo JSON</h3>
                {examOptions.map((option) => (
                  <button
                    disabled={exampleMutation.isPending}
                    key={option.value}
                    onClick={() => exampleMutation.mutate(option.value)}
                    type="button"
                  >
                    {option.label}
                  </button>
                ))}
                {exampleMutation.error && (
                  <p className="notice notice--error" role="alert">
                    Não foi possível baixar o exemplo.
                  </p>
                )}
              </div>
            </section>
          </>
        )}
      </main>
    </>
  );
}
