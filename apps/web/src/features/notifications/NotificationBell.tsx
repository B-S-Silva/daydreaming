import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Link, useNavigate } from "react-router";
import {
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "./notifications-api.js";

interface NotificationBellProps {
  authenticated: boolean;
  csrfToken?: string;
}

export function NotificationBell({ authenticated, csrfToken }: NotificationBellProps) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const notificationsQuery = useQuery({
    queryKey: ["notifications", "unread"],
    queryFn: () => getNotifications("unread", 5),
    enabled: authenticated,
  });
  const markMutation = useMutation({
    mutationFn: (id: number) => markNotificationRead(id, csrfToken ?? ""),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });
  const markAllMutation = useMutation({
    mutationFn: () => markAllNotificationsRead(csrfToken ?? ""),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });

  if (!authenticated) {
    return null;
  }

  const data = notificationsQuery.data;
  return (
    <details className="notification-bell">
      <summary aria-label="Abrir notificações">
        🔔
        {Boolean(data?.unreadCount) && (
          <span className="notification-bell__count">
            {data && data.unreadCount > 9 ? "9+" : data?.unreadCount}
          </span>
        )}
      </summary>
      <section className="notification-dropdown">
        <header>
          <strong>Notificações</strong>
          {Boolean(data?.unreadCount) && (
            <button
              disabled={markAllMutation.isPending}
              onClick={() => markAllMutation.mutate()}
              type="button"
            >
              Marcar todas como lidas
            </button>
          )}
        </header>
        {notificationsQuery.isPending && <p role="status">Carregando...</p>}
        {notificationsQuery.error && <p role="alert">Não foi possível carregar as notificações.</p>}
        {(markMutation.error || markAllMutation.error) && (
          <p role="alert">Não foi possível atualizar as notificações.</p>
        )}
        {data?.items.length === 0 && <p>Nenhuma notificação nova</p>}
        {data?.items.map((item) => (
          <Link
            className="notification-dropdown__item"
            key={item.id}
            onClick={(event) => {
              event.preventDefault();
              markMutation.mutate(item.id, {
                onSuccess: () => navigate(item.link ?? "/notificacoes"),
              });
            }}
            to={item.link ?? "/notificacoes"}
          >
            <strong>{item.title}</strong>
            <span>{item.message}</span>
          </Link>
        ))}
        {data && data.unreadCount > 0 && (
          <footer>
            <Link to="/notificacoes">Ver todas as notificações</Link>
          </footer>
        )}
      </section>
    </details>
  );
}
