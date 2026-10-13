import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Link } from "react-router";
import { getCurrentUser } from "../auth/auth-api.js";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import { ForumApiError, getForumAdminDashboard, submitForumMutation } from "./forum-api.js";
import type { ForumAdminDashboardResponse } from "@daydreaming/shared";

type ForumAdminTab = "topicos" | "usuarios" | "categorias" | "logs";

function formatDate(value: string): string {
  const date = new Date(value);
  return Number.isNaN(date.valueOf()) ? value : date.toLocaleString("pt-BR");
}

function getErrorMessage(error: unknown): string {
  return error instanceof ForumApiError
    ? error.message
    : "Não foi possível carregar o painel administrativo.";
}

function TopicActions({
  topic,
  onAction,
}: {
  topic: ForumAdminDashboardResponse["topics"][number];
  onAction: (action: string) => void;
}) {
  return (
    <div className="forum-admin-actions">
      {!topic.aprovado && (
        <>
          <button onClick={() => onAction("aprovar")} type="button">
            Aprovar
          </button>
          <button onClick={() => onAction("rejeitar")} type="button">
            Rejeitar
          </button>
        </>
      )}
      <button onClick={() => onAction(topic.fixado ? "desfixar" : "fixar")} type="button">
        {topic.fixado ? "Desfixar" : "Fixar"}
      </button>
      <button onClick={() => onAction(topic.fechado ? "abrir" : "fechar")} type="button">
        {topic.fechado ? "Reabrir" : "Fechar"}
      </button>
      <button
        className="forum-admin-actions__danger"
        onClick={() => {
          if (window.confirm("Tem certeza que deseja deletar este tópico?")) {
            onAction("deletar");
          }
        }}
        type="button"
      >
        Excluir
      </button>
    </div>
  );
}

export function ForumAdminPage() {
  const [tab, setTab] = useState<ForumAdminTab>("topicos");
  const [filter, setFilter] = useState("todos");
  const [categoryName, setCategoryName] = useState("");
  const [categoryDescription, setCategoryDescription] = useState("");
  const [categoryColor, setCategoryColor] = useState("#187bcd");
  const [categoryIcon, setCategoryIcon] = useState("💬");
  const [message, setMessage] = useState<string | null>(null);
  const queryClient = useQueryClient();
  const currentUserQuery = useQuery({ queryKey: ["auth-me"], queryFn: getCurrentUser });
  const dashboardQuery = useQuery({
    queryKey: ["forum-admin-dashboard", filter],
    queryFn: () => getForumAdminDashboard(filter),
    enabled: currentUserQuery.data?.authenticated === true && currentUserQuery.data.user.isAdmin,
  });
  const actionMutation = useMutation({
    mutationFn: ({ path, input }: { path: string; input: unknown }) =>
      submitForumMutation(path, input),
    onSuccess: async (result) => {
      setMessage(result.message);
      await queryClient.invalidateQueries({ queryKey: ["forum-admin-dashboard"] });
      await queryClient.invalidateQueries({ queryKey: ["forum-topics"] });
      await queryClient.invalidateQueries({ queryKey: ["forum-topic"] });
      await queryClient.invalidateQueries({ queryKey: ["forum-categories"] });
    },
  });
  const dashboard = dashboardQuery.data;
  const currentUser = currentUserQuery.data;

  function moderate(type: "topico" | "resposta", id: number, action: string) {
    actionMutation.mutate({
      path: "/api/admin/forum/moderation",
      input: { tipo: type, id, acao: action },
    });
  }

  function changeUser(id: number, action: string) {
    let reason: string | undefined;
    if (action === "banir") {
      const enteredReason = window.prompt("Motivo do banimento:");
      if (enteredReason === null || enteredReason === "") {
        return;
      }
      reason = enteredReason;
    } else if (!window.confirm("Confirmar esta alteração de usuário?")) {
      return;
    }
    actionMutation.mutate({
      path: "/api/admin/forum/users/action",
      input: {
        usuario_id: id,
        acao: action,
        ...(reason === undefined ? {} : { motivo: reason }),
      },
    });
  }

  return (
    <>
      <PublicHeader />
      <main className="forum-page content-width">
        <header className="forum-page__heading">
          <div>
            <h1>⚙️ Administração do Fórum</h1>
            <p>Modere discussões, gerencie usuários e organize categorias.</p>
          </div>
          <Link className="button button--secondary" to="/forum">
            Voltar ao fórum
          </Link>
        </header>
        {currentUserQuery.isPending && (
          <p className="notice" role="status">
            Verificando acesso...
          </p>
        )}
        {currentUserQuery.error && (
          <p className="notice notice--error" role="alert">
            {getErrorMessage(currentUserQuery.error)}
          </p>
        )}
        {currentUser && !currentUser.authenticated && (
          <p className="notice notice--error" role="alert">
            Faça <Link to="/login">login</Link> para acessar este painel.
          </p>
        )}
        {currentUser?.authenticated && !currentUser.user.isAdmin && (
          <p className="notice notice--error" role="alert">
            Acesso restrito a administradores.
          </p>
        )}
        {dashboardQuery.isPending && currentUser?.authenticated && currentUser.user.isAdmin && (
          <p className="notice" role="status">
            Carregando painel...
          </p>
        )}
        {dashboardQuery.error && (
          <p className="notice notice--error" role="alert">
            {getErrorMessage(dashboardQuery.error)}
          </p>
        )}
        {message && (
          <p className="notice" role="status">
            {message}
          </p>
        )}
        {actionMutation.error && (
          <p className="notice notice--error" role="alert">
            {getErrorMessage(actionMutation.error)}
          </p>
        )}

        {dashboard && currentUser?.authenticated && currentUser.user.isAdmin && (
          <>
            <section className="forum-admin-stats" aria-label="Estatísticas do fórum">
              <article>
                <strong>{dashboard.stats.total_topicos}</strong>
                <span>Total de tópicos</span>
              </article>
              <article>
                <strong>{dashboard.stats.topicos_pendentes}</strong>
                <span>Tópicos pendentes</span>
              </article>
              <article>
                <strong>{dashboard.stats.total_respostas}</strong>
                <span>Total de respostas</span>
              </article>
              <article>
                <strong>{dashboard.stats.respostas_pendentes}</strong>
                <span>Respostas pendentes</span>
              </article>
              <article>
                <strong>{dashboard.stats.total_usuarios}</strong>
                <span>Usuários</span>
              </article>
            </section>

            <nav className="forum-admin-tabs" aria-label="Seções administrativas">
              {(
                [
                  ["topicos", "Tópicos"],
                  ["usuarios", "Usuários"],
                  ["categorias", "Categorias"],
                  ["logs", "Logs"],
                ] as const
              ).map(([value, label]) => (
                <button
                  aria-pressed={tab === value}
                  className={tab === value ? "is-active" : ""}
                  key={value}
                  onClick={() => setTab(value)}
                  type="button"
                >
                  {label}
                </button>
              ))}
            </nav>

            {tab === "topicos" && (
              <section className="forum-panel">
                <div className="forum-admin-section-heading">
                  <h2>Gerenciar tópicos</h2>
                  <label>
                    Filtro
                    <select value={filter} onChange={(event) => setFilter(event.target.value)}>
                      <option value="todos">Todos</option>
                      <option value="pendentes">Pendentes</option>
                      <option value="fixados">Fixados</option>
                      <option value="fechados">Fechados</option>
                    </select>
                  </label>
                </div>
                {dashboard.topics.length === 0 && (
                  <p className="notice">Nenhum tópico encontrado.</p>
                )}
                {dashboard.topics.map((topic) => (
                  <article className="forum-admin-item" key={topic.id}>
                    <div className="forum-admin-item__heading">
                      <h3>{topic.titulo}</h3>
                      <span>
                        {topic.aprovado ? "Aprovado" : "Pendente"}
                        {topic.fixado ? " · Fixado" : ""}
                        {topic.fechado ? " · Fechado" : ""}
                      </span>
                    </div>
                    <p className="forum-content">
                      {topic.conteudo.slice(0, 200)}
                      {topic.conteudo.length > 200 ? "…" : ""}
                    </p>
                    <p className="forum-topic-meta">
                      {topic.autor_nome} · {topic.categoria_nome} · {formatDate(topic.data_criacao)}{" "}
                      · {topic.total_respostas} respostas
                    </p>
                    <TopicActions
                      onAction={(action) => moderate("topico", topic.id, action)}
                      topic={topic}
                    />
                  </article>
                ))}
                {dashboard.pendingReplies.length > 0 && (
                  <div className="forum-admin-pending">
                    <h2>Respostas pendentes</h2>
                    {dashboard.pendingReplies.map((reply) => (
                      <article className="forum-admin-item" key={reply.id}>
                        <h3>Resposta em: {reply.topico_titulo}</h3>
                        <p className="forum-content">{reply.conteudo}</p>
                        <p className="forum-topic-meta">
                          {reply.autor_nome} · {formatDate(reply.data_criacao)}
                        </p>
                        <div className="forum-admin-actions">
                          <button
                            onClick={() => moderate("resposta", reply.id, "aprovar")}
                            type="button"
                          >
                            Aprovar
                          </button>
                          <button
                            onClick={() => moderate("resposta", reply.id, "rejeitar")}
                            type="button"
                          >
                            Rejeitar
                          </button>
                          <button
                            className="forum-admin-actions__danger"
                            onClick={() => {
                              if (window.confirm("Tem certeza que deseja deletar esta resposta?")) {
                                moderate("resposta", reply.id, "deletar");
                              }
                            }}
                            type="button"
                          >
                            Excluir
                          </button>
                        </div>
                      </article>
                    ))}
                  </div>
                )}
              </section>
            )}

            {tab === "usuarios" && (
              <section className="forum-panel">
                <h2>Gerenciar usuários</h2>
                {dashboard.users.length === 0 && (
                  <p className="notice">Nenhum usuário encontrado.</p>
                )}
                {dashboard.users.map((user) => (
                  <article className="forum-admin-item" key={user.id}>
                    <div className="forum-admin-item__heading">
                      <h3>
                        {user.nome}{" "}
                        <span>
                          {user.is_admin ? "· Administrador" : "· Usuário"}
                          {user.ativo ? "" : " · Banido"}
                        </span>
                      </h3>
                      <span>{user.email}</span>
                    </div>
                    <p className="forum-topic-meta">
                      @{user.usuario} · Cadastro: {formatDate(user.data_criacao)} ·{" "}
                      {user.total_topicos} tópicos · {user.total_respostas} respostas
                    </p>
                    {user.id !== currentUser.user.id && (
                      <div className="forum-admin-actions">
                        <button
                          onClick={() =>
                            changeUser(user.id, user.is_admin ? "rebaixar" : "promover")
                          }
                          type="button"
                        >
                          {user.is_admin ? "Rebaixar" : "Promover"}
                        </button>
                        <button
                          className={user.ativo ? "forum-admin-actions__danger" : ""}
                          onClick={() => changeUser(user.id, user.ativo ? "banir" : "desbanir")}
                          type="button"
                        >
                          {user.ativo ? "Banir" : "Desbanir"}
                        </button>
                      </div>
                    )}
                  </article>
                ))}
              </section>
            )}

            {tab === "categorias" && (
              <section className="forum-panel">
                <h2>Gerenciar categorias</h2>
                <form
                  className="forum-form forum-category-form"
                  onSubmit={(event) => {
                    event.preventDefault();
                    actionMutation.mutate({
                      path: "/api/admin/forum/categories",
                      input: {
                        nome: categoryName,
                        descricao: categoryDescription,
                        cor: categoryColor,
                        icone: categoryIcon,
                      },
                    });
                    setCategoryName("");
                    setCategoryDescription("");
                  }}
                >
                  <label>
                    Nome
                    <input
                      onChange={(event) => setCategoryName(event.target.value)}
                      required
                      value={categoryName}
                    />
                  </label>
                  <label>
                    Descrição
                    <textarea
                      onChange={(event) => setCategoryDescription(event.target.value)}
                      value={categoryDescription}
                    />
                  </label>
                  <label>
                    Cor
                    <input
                      onChange={(event) => setCategoryColor(event.target.value)}
                      type="color"
                      value={categoryColor}
                    />
                  </label>
                  <label>
                    Ícone
                    <input
                      maxLength={2}
                      onChange={(event) => setCategoryIcon(event.target.value)}
                      value={categoryIcon}
                    />
                  </label>
                  <button className="button button--primary" disabled={actionMutation.isPending}>
                    Criar categoria
                  </button>
                </form>
                {dashboard.categories.length === 0 && (
                  <p className="notice">Nenhuma categoria cadastrada.</p>
                )}
                <div className="forum-category-list">
                  {dashboard.categories.map((category) => (
                    <article className="forum-admin-item" key={category.id}>
                      <span
                        className="forum-category-pill"
                        style={{ backgroundColor: category.cor }}
                      >
                        {category.icone} {category.nome}
                      </span>
                      <p>{category.descricao}</p>
                    </article>
                  ))}
                </div>
              </section>
            )}

            {tab === "logs" && (
              <section className="forum-panel">
                <h2>Logs de moderação</h2>
                {dashboard.logs.length === 0 && (
                  <p className="notice">Nenhuma ação de moderação registrada.</p>
                )}
                {dashboard.logs.map((log) => (
                  <article className="forum-admin-item" key={log.id}>
                    <strong>{log.moderador_nome}</strong> {log.acao}
                    {log.tipo_conteudo ? ` · ${log.tipo_conteudo}` : ""}
                    <span className="forum-topic-meta"> · {formatDate(log.data_acao)}</span>
                    {log.motivo && <p>Motivo: {log.motivo}</p>}
                  </article>
                ))}
              </section>
            )}
          </>
        )}
      </main>
    </>
  );
}
