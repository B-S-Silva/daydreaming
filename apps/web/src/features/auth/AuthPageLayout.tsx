import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { Link } from "react-router";
import { AuthApiError, getCurrentUser, logout } from "./auth-api.js";

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
      <div className="public-header__identity">
        {currentUserQuery.isPending ? (
          <span className="public-header__status" aria-live="polite">
            Verificando sessão...
          </span>
        ) : currentUser?.authenticated ? (
          <span className="public-header__status">✅ Olá, {currentUser.user.name}</span>
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
      </div>
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
          <h1>{title}</h1>
          {children}
        </section>
      </main>
    </>
  );
}
