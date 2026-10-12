import type { ReactNode } from "react";
import { Link } from "react-router";

interface AuthPageLayoutProps {
  title: string;
  children: ReactNode;
}

export function PublicHeader() {
  return (
    <header className="public-header">
      <div className="public-header__identity">
        <span className="public-header__status">❌ Você não está logado</span>
        <Link className="public-header__home" to="/">
          🏠 Página Inicial
        </Link>
      </div>
      <Link className="public-header__login" to="/login">
        🔑 Fazer Login
      </Link>
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
