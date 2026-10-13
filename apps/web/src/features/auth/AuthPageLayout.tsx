import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { Link } from "react-router";
import { AuthApiError, getCurrentUser, logout } from "./auth-api.js";
import { NotificationBell } from "../notifications/NotificationBell.js";

interface AuthPageLayoutProps {
  title: string;
  children: ReactNode;
}

export function PublicHeader() {
  const queryClient = useQueryClient();
  const currentUserQuery = useQuery({
    queryKey: ["auth-me"],
    queryFn: getCurrentUser,
  });
  const logoutMutation = useMutation({
    mutationFn: logout,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["auth-me"] });
    },
  });
  const currentUser = currentUserQuery.data;

  return (
    <header className="public-header">
      <Link className="public-header__brand" to="/" aria-label="DayDreaming Início">
        <img
          className="public-header__logo"
          src="/images/logo-daydreaming.png"
          alt="DayDreaming Logo"
        />
      </Link>
      <div className="public-header__identity">
        {currentUserQuery.isPending ? (
          <span className="public-header__status" aria-live="polite">
            Verificando sessão...
          </span>
        ) : currentUser?.authenticated ? (
          <span className="public-header__status public-header__status--user">
            ✅ Olá, {currentUser.user.name}
          </span>
        ) : (
          <span className="public-header__status">❌ Você não está logado</span>
        )}
        {currentUser?.flashMessage && (
          <span className="public-header__status" role="status">
            {currentUser.flashMessage}
          </span>
        )}
        {currentUserQuery.error && (
          <span className="public-header__status" role="alert">
            {currentUserQuery.error instanceof AuthApiError
              ? currentUserQuery.error.message
              : "Não foi possível verificar a sessão."}
          </span>
        )}
        <Link className="public-header__home" to="/">
          🏠 Página Inicial
        </Link>
        <Link className="public-header__home" to="/ferramentas-estudo">
          📚 Ferramentas de estudo
        </Link>
        {currentUser?.authenticated && (
          <Link className="public-header__home" to="/perfil">
            👤 Meu perfil
          </Link>
        )}
        {currentUser?.authenticated && currentUser.user.isAdmin && (
          <Link className="public-header__home" to="/admin/questoes">
            📝 Gerenciar Questões
          </Link>
        )}
        {currentUser?.authenticated && (
          <Link className="public-header__home" to="/forum">
            💬 Fórum
          </Link>
        )}
        {currentUser?.authenticated && currentUser.user.isAdmin && (
          <Link className="public-header__home" to="/admin/forum">
            ⚙️ Admin do Fórum
          </Link>
        )}
        {currentUser?.authenticated && (
          <NotificationBell authenticated csrfToken={currentUser.csrfToken} />
        )}
      </div>
      <div className="public-header__actions">
        {currentUser?.authenticated ? (
          <button
            className="public-header__login"
            disabled={logoutMutation.isPending}
            onClick={() => logoutMutation.mutate(currentUser.csrfToken)}
            type="button"
          >
            {logoutMutation.isPending ? "SAINDO..." : "🚪 Sair"}
          </button>
        ) : (
          <Link className="public-header__login" to="/login">
            🔑 Fazer Login
          </Link>
        )}
      </div>
      {logoutMutation.error && (
        <span className="public-header__status" role="alert">
          {logoutMutation.error instanceof AuthApiError
            ? logoutMutation.error.message
            : "Não foi possível encerrar a sessão."}
        </span>
      )}
    </header>
  );
}

export function AuthPageLayout({ title, children }: AuthPageLayoutProps) {
  return (
    <>
      <PublicHeader />
      <main className="auth-page">
        <section className="auth-card">
          <div style={{ textAlign: "center", marginBottom: "20px" }}>
            <Link to="/">
              <img
                src="/images/logo-daydreaming.png"
                alt="DayDreaming Logo"
                style={{ height: "46px", margin: "0 auto", objectFit: "contain" }}
              />
            </Link>
          </div>
          <h1>{title}</h1>
          {children}
        </section>
      </main>
    </>
  );
}
