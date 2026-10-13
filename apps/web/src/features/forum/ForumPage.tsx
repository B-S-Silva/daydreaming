import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Link, useParams, useSearchParams } from "react-router";
import { getCurrentUser } from "../auth/auth-api.js";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import {
  ForumApiError,
  getForumCategories,
  getForumTopic,
  getForumTopics,
  submitForumMutation,
} from "./forum-api.js";

function formatDate(value: string): string {
  const date = new Date(value);
  return Number.isNaN(date.valueOf()) ? value : date.toLocaleString("pt-BR");
}

function formatContent(value: string): string {
  return value.slice(0, 200);
}

function ForumNotice({ error }: { error: unknown }) {
  return (
    <p className="notice notice--error" role="alert">
      {error instanceof ForumApiError ? error.message : "Não foi possível carregar o fórum."}
    </p>
  );
}

export function ForumPage() {
  const { id } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const [searchValue, setSearchValue] = useState(searchParams.get("busca") ?? "");
  const [categoryId, setCategoryId] = useState(searchParams.get("categoria") ?? "");
  const [newTitle, setNewTitle] = useState("");
  const [newContent, setNewContent] = useState("");
  const [newCategoryId, setNewCategoryId] = useState("");
  const [replyContent, setReplyContent] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const queryClient = useQueryClient();
  const currentUserQuery = useQuery({ queryKey: ["auth-me"], queryFn: getCurrentUser });
  const categoriesQuery = useQuery({
    queryKey: ["forum-categories"],
    queryFn: getForumCategories,
    enabled: currentUserQuery.data?.authenticated === true,
  });
  const topicsQuery = useQuery({
    queryKey: ["forum-topics", categoryId, searchParams.get("busca")],
    queryFn: () => {
      const category = categoryId ? Number(categoryId) : undefined;
      const search = searchParams.get("busca") ?? undefined;
      return getForumTopics({
        ...(category === undefined ? {} : { categoria: category }),
        ...(search === undefined ? {} : { busca: search }),
      });
    },
    enabled: currentUserQuery.data?.authenticated === true && !id,
  });
  const topicQuery = useQuery({
    queryKey: ["forum-topic", id],
    queryFn: () => getForumTopic(id ?? ""),
    enabled: currentUserQuery.data?.authenticated === true && Boolean(id),
  });
  const mutation = useMutation({
    mutationFn: ({ path, input }: { path: string; input: unknown }) =>
      submitForumMutation(path, input),
    onSuccess: async (result) => {
      setMessage(result.message);
      setNewTitle("");
      setNewContent("");
      setReplyContent("");
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["forum-topics"] }),
        queryClient.invalidateQueries({ queryKey: ["forum-topic"] }),
      ]);
    },
  });
  const currentUser = currentUserQuery.data;

  return (
    <>
      <PublicHeader />
      <main className="forum-page content-width">
        <header className="forum-page__heading">
          <div>
            <h1>🌟 Fórum da Comunidade</h1>
            <p>Participe das discussões e compartilhe conhecimento.</p>
          </div>
          {currentUser?.authenticated && (
            <span>
              Bem-vindo, <strong>{currentUser.user.name}</strong>!
            </span>
          )}
        </header>

        {currentUserQuery.isPending && (
          <p className="notice" role="status">
            Verificando sessão...
          </p>
        )}
        {currentUserQuery.error && <ForumNotice error={currentUserQuery.error} />}
        {currentUser && !currentUser.authenticated && (
          <p className="notice notice--error" role="alert">
            Faça <Link to="/login">login</Link> para acessar o fórum.
          </p>
        )}
        {message && (
          <p className="notice" role="status">
            {message}
          </p>
        )}
        {mutation.error && <ForumNotice error={mutation.error} />}

        {currentUser?.authenticated && (
          <>
            {categoriesQuery.isPending && (
              <p className="notice" role="status">
                Carregando categorias...
              </p>
            )}
            {categoriesQuery.error && <ForumNotice error={categoriesQuery.error} />}
            {id ? (
              <section className="forum-panel">
                <Link className="button button--secondary" to="/forum">
                  ← Voltar ao Fórum
                </Link>
                {topicQuery.isPending && <p role="status">Carregando tópico...</p>}
                {topicQuery.error && <ForumNotice error={topicQuery.error} />}
                {topicQuery.data && (
                  <>
                    <article className="forum-topic-detail">
                      <div className="forum-topic-detail__heading">
                        <span
                          className="forum-category-pill"
                          style={{ backgroundColor: topicQuery.data.topic.categoria_cor }}
                        >
                          {topicQuery.data.topic.categoria_icone}{" "}
                          {topicQuery.data.topic.categoria_nome}
                        </span>
                        <h2>
                          {topicQuery.data.topic.fixado ? "📌 " : ""}
                          {topicQuery.data.topic.fechado ? "🔒 " : ""}
                          {topicQuery.data.topic.titulo}
                        </h2>
                      </div>
                      <p className="forum-content">{topicQuery.data.topic.conteudo}</p>
                      <p className="forum-topic-meta">
                        Por {topicQuery.data.topic.autor_nome} ·{" "}
                        {formatDate(topicQuery.data.topic.data_criacao)} ·{" "}
                        {topicQuery.data.topic.visualizacoes} visualizações
                      </p>
                      <button
                        className="forum-like-button"
                        onClick={() =>
                          mutation.mutate({
                            path: "/api/forum/likes",
                            input: { tipo: "topico", id: topicQuery.data.topic.id },
                          })
                        }
                        type="button"
                      >
                        ❤️ {topicQuery.data.topic.total_likes}
                      </button>
                    </article>
                    <section className="forum-replies" aria-labelledby="forum-replies-heading">
                      <h3 id="forum-replies-heading">
                        💬 Respostas ({topicQuery.data.replies.length})
                      </h3>
                      {topicQuery.data.replies.length === 0 && (
                        <p className="notice">Ainda não há respostas neste tópico.</p>
                      )}
                      {topicQuery.data.replies.map((reply) => (
                        <article className="forum-reply-card" key={reply.id}>
                          <p className="forum-content">{reply.conteudo}</p>
                          <div className="forum-topic-meta">
                            <span>
                              {reply.autor_nome} · {formatDate(reply.data_criacao)}
                            </span>
                            <button
                              className="forum-like-button"
                              onClick={() =>
                                mutation.mutate({
                                  path: "/api/forum/likes",
                                  input: { tipo: "resposta", id: reply.id },
                                })
                              }
                              type="button"
                            >
                              ❤️ {reply.total_likes}
                            </button>
                          </div>
                        </article>
                      ))}
                      {!topicQuery.data.topic.fechado ? (
                        <form
                          className="forum-form"
                          onSubmit={(event) => {
                            event.preventDefault();
                            mutation.mutate({
                              path: "/api/forum/replies",
                              input: {
                                topico_id: topicQuery.data.topic.id,
                                conteudo: replyContent,
                              },
                            });
                          }}
                        >
                          <label>
                            Adicionar resposta
                            <textarea
                              onChange={(event) => setReplyContent(event.target.value)}
                              required
                              value={replyContent}
                            />
                          </label>
                          <button className="button button--primary" disabled={mutation.isPending}>
                            Enviar resposta
                          </button>
                        </form>
                      ) : (
                        <p className="notice">🔒 Este tópico está fechado para novas respostas.</p>
                      )}
                    </section>
                  </>
                )}
              </section>
            ) : (
              <>
                <section className="forum-panel">
                  <h2>✨ Novo tópico</h2>
                  {categoriesQuery.data?.items.length === 0 && (
                    <p className="notice">
                      Ainda não há categorias disponíveis. Um administrador precisa criar uma.
                    </p>
                  )}
                  <form
                    className="forum-form"
                    onSubmit={(event) => {
                      event.preventDefault();
                      mutation.mutate({
                        path: "/api/forum/topics",
                        input: {
                          categoria_id: Number(newCategoryId),
                          titulo: newTitle,
                          conteudo: newContent,
                        },
                      });
                    }}
                  >
                    <label>
                      Categoria
                      <select
                        onChange={(event) => setNewCategoryId(event.target.value)}
                        required
                        value={newCategoryId}
                      >
                        <option value="">Selecione uma categoria</option>
                        {categoriesQuery.data?.items.map((category) => (
                          <option key={category.id} value={category.id}>
                            {category.icone} {category.nome}
                          </option>
                        ))}
                      </select>
                    </label>
                    <label>
                      Título
                      <input
                        onChange={(event) => setNewTitle(event.target.value)}
                        required
                        value={newTitle}
                      />
                    </label>
                    <label>
                      Conteúdo
                      <textarea
                        onChange={(event) => setNewContent(event.target.value)}
                        required
                        value={newContent}
                      />
                    </label>
                    <button
                      className="button button--primary"
                      disabled={mutation.isPending || !categoriesQuery.data?.items.length}
                    >
                      Criar tópico
                    </button>
                  </form>
                </section>
                <section className="forum-panel" aria-labelledby="forum-topics-heading">
                  <h2 id="forum-topics-heading">Discussões recentes</h2>
                  <form
                    className="forum-search"
                    onSubmit={(event) => {
                      event.preventDefault();
                      const next = new URLSearchParams();
                      if (categoryId) next.set("categoria", categoryId);
                      if (searchValue.trim()) next.set("busca", searchValue.trim());
                      setSearchParams(next);
                    }}
                  >
                    <input
                      aria-label="Buscar tópicos"
                      onChange={(event) => setSearchValue(event.target.value)}
                      placeholder="Buscar tópicos..."
                      value={searchValue}
                    />
                    <select
                      aria-label="Filtrar por categoria"
                      onChange={(event) => setCategoryId(event.target.value)}
                      value={categoryId}
                    >
                      <option value="">Todas as categorias</option>
                      {categoriesQuery.data?.items.map((category) => (
                        <option key={category.id} value={category.id}>
                          {category.nome}
                        </option>
                      ))}
                    </select>
                    <button className="button button--secondary">Buscar</button>
                  </form>
                  {topicsQuery.isPending && <p role="status">Carregando tópicos...</p>}
                  {topicsQuery.error && <ForumNotice error={topicsQuery.error} />}
                  {topicsQuery.data?.items.length === 0 && (
                    <p className="notice">
                      🤔 Nenhum tópico encontrado. Seja o primeiro a iniciar uma discussão!
                    </p>
                  )}
                  <div className="forum-topic-list">
                    {topicsQuery.data?.items.map((topic) => (
                      <article className="forum-topic-card" key={topic.id}>
                        <Link to={`/forum/topics/${topic.id}`}>
                          <div className="forum-topic-card__heading">
                            <h3>
                              {topic.fixado ? "📌 " : ""}
                              {topic.fechado ? "🔒 " : ""}
                              {topic.titulo}
                            </h3>
                            <span
                              className="forum-category-pill"
                              style={{ backgroundColor: topic.categoria_cor }}
                            >
                              {topic.categoria_icone} {topic.categoria_nome}
                            </span>
                          </div>
                          <p className="forum-content">
                            {formatContent(topic.conteudo)}
                            {topic.conteudo.length > 200 ? "…" : ""}
                          </p>
                          <span className="forum-topic-meta">
                            {topic.autor_nome} · {formatDate(topic.data_atualizacao)}
                          </span>
                          <span className="forum-topic-meta">
                            👁️ {topic.visualizacoes} · 💬 {topic.total_respostas} · ❤️{" "}
                            {topic.total_likes}
                          </span>
                        </Link>
                      </article>
                    ))}
                  </div>
                </section>
              </>
            )}
          </>
        )}
      </main>
    </>
  );
}
