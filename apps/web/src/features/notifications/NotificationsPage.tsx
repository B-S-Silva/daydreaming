import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Link, useNavigate } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import { getCurrentUser } from "../auth/auth-api.js";
import {
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  NotificationsApiError,
} from "./notifications-api.js";

function iconFor(type: string): string {
  switch (type) {
    case "forum_reply":
      return "💬";
    case "forum_mention":
      return "👤";
    case "badge":
      return "🏆";
    case "level":
      return "⭐";
    default:
      return "📢";
  }
}

export function NotificationsPage() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const currentUserQuery = useQuery({
    queryKey: ["auth-me"],
    queryFn: getCurrentUser,
  });
  const csrfToken = currentUserQuery.data?.authenticated
    ? currentUserQuery.data.csrfToken
    : undefined;
  const notificationsQuery = useQuery({
    queryKey: ["notifications", "all"],
    queryFn: () => getNotifications("all", 100),
    enabled: currentUserQuery.data?.authenticated === true,
  });
  const markAllMutation = useMutation({
    mutationFn: () => markAllNotificationsRead(csrfToken ?? ""),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
  const markMutation = useMutation({
    mutationFn: (id: number) => markNotificationRead(id, csrfToken ?? ""),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });

  return (
    <>
      <PublicHeader />
      <main className="notifications-page content-width">
        <header className="results-heading">
          <h1>Notificações</h1>
          <p>
            {notificationsQuery.data && notificationsQuery.data.unreadCount > 0
              ? `Você tem ${notificationsQuery.data.unreadCount} notificação${
                  notificationsQuery.data.unreadCount === 1 ? "" : "ões"
                } não lida${notificationsQuery.data.unreadCount === 1 ? "" : "s"}.`
              : "Todas as notificações foram lidas."}
          </p>
          {notificationsQuery.data && notificationsQuery.data.unreadCount > 0 && (
            <button
              className="button button--secondary"
              disabled={markAllMutation.isPending}
              onClick={() => markAllMutation.mutate()}
              type="button"
            >
              {markAllMutation.isPending ? "Atualizando..." : "Marcar todas como lidas"}
            </button>
          )}
        </header>
        {currentUserQuery.isPending && <p role="status">Verificando sessão...</p>}
        {currentUserQuery.error && <p role="alert">Não foi possível verificar sua sessão.</p>}
        {currentUserQuery.data?.authenticated === false && (
          <section className="notification-card">
            <p>Entre na sua conta para acessar as notificações.</p>
            <Link className="button button--primary" to="/login">
              Fazer login
            </Link>
          </section>
        )}
        {currentUserQuery.data?.authenticated && notificationsQuery.isPending && (
          <p role="status">Carregando notificações...</p>
        )}
        {notificationsQuery.error && (
          <section className="notification-card" role="alert">
            <p>
              {notificationsQuery.error instanceof NotificationsApiError
                ? notificationsQuery.error.message
                : "Não foi possível carregar as notificações."}
            </p>
            {notificationsQuery.error instanceof NotificationsApiError &&
              notificationsQuery.error.statusCode === 401 && (
                <Link className="button button--primary" to="/login">
                  Fazer login
                </Link>
              )}
          </section>
        )}
        {notificationsQuery.data?.items.length === 0 && (
          <section className="notification-empty">
            <span aria-hidden="true">🔔</span>
            <h2>Nenhuma notificação</h2>
            <p>Quando houver atividades relevantes, elas aparecerão aqui.</p>
          </section>
        )}
        <div className="notification-list">
          {notificationsQuery.data?.items.map((item) => (
            <article className={`notification-card${item.read ? "" : " is-unread"}`} key={item.id}>
              <span className="notification-card__icon" aria-hidden="true">
                {iconFor(item.type)}
              </span>
              <div className="notification-card__content">
                <div className="notification-card__title">
                  <h2>{item.title}</h2>
                  {!item.read && <span className="notification-new">Nova</span>}
                </div>
                <p>{item.message}</p>
                <time dateTime={item.createdAt}>
                  {new Intl.DateTimeFormat("pt-BR", {
                    dateStyle: "short",
                    timeStyle: "short",
                  }).format(new Date(item.createdAt))}
                </time>
              </div>
              {!item.read && (
                <button
                  className="notification-mark-read"
                  disabled={markMutation.isPending}
                  onClick={() => markMutation.mutate(item.id)}
                  type="button"
                >
                  Marcar como lida
                </button>
              )}
              {markMutation.error && (
                <span className="notification-error" role="alert">
                  {markMutation.error instanceof NotificationsApiError
                    ? markMutation.error.message
                    : "Não foi possível atualizar a notificação."}
                </span>
              )}
              {item.link && (
                <Link
                  className="notification-card__open"
                  onClick={(event) => {
                    if (!item.read && csrfToken) {
                      event.preventDefault();
                      markMutation.mutate(item.id, {
                        onSuccess: () => navigate(item.link ?? "/notificacoes"),
                      });
                    }
                  }}
                  to={item.link}
                >
                  Abrir
                </Link>
              )}
            </article>
          ))}
        </div>
      </main>
    </>
  );
}
