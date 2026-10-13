import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const memberSession = {
  authenticated: true,
  user: { id: 2, name: "Membro", username: "membro", isAdmin: false },
  csrfToken: "e".repeat(64),
  flashMessage: null,
};

const adminSession = {
  ...memberSession,
  user: { ...memberSession.user, id: 1, name: "Admin", username: "admin", isAdmin: true },
};

const emptyDashboard = {
  stats: {
    total_topicos: 0,
    topicos_pendentes: 0,
    total_respostas: 0,
    respostas_pendentes: 0,
    total_usuarios: 0,
  },
  topics: [],
  pendingReplies: [],
  categories: [],
  users: [],
  logs: [],
};

function openRoute(path: string) {
  window.history.pushState({}, "", path);
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("forum pages", () => {
  it("renders the forum empty state and offers category/topic filtering", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        const path = String(input);
        if (path === "/api/auth/me") {
          return new Response(JSON.stringify(memberSession), { status: 200 });
        }
        if (path === "/api/forum/categories") {
          return new Response(
            JSON.stringify({
              items: [
                { id: 3, nome: "Geral", descricao: null, cor: "#187bcd", icone: "💬", ativo: true },
              ],
            }),
            { status: 200 },
          );
        }
        if (path.startsWith("/api/forum/topics")) {
          return new Response(JSON.stringify({ items: [] }), { status: 200 });
        }
        throw new Error(`Unexpected request: ${path}`);
      }),
    );
    openRoute("/forum");

    expect(await screen.findByText(/Nenhum tópico encontrado/)).toBeTruthy();
    expect(screen.getByRole("combobox", { name: "Filtrar por categoria" })).toBeTruthy();
    expect(screen.getByRole("heading", { name: "✨ Novo tópico" })).toBeTruthy();
  });

  it("renders admin empty states and posts new categories with the CSRF token", async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return new Response(JSON.stringify(adminSession), { status: 200 });
      }
      if (path.startsWith("/api/admin/forum/dashboard")) {
        return new Response(JSON.stringify(emptyDashboard), { status: 200 });
      }
      if (path === "/api/admin/forum/categories") {
        expect(init?.headers).toEqual({
          "content-type": "application/json",
          "x-csrf-token": adminSession.csrfToken,
        });
        return new Response(
          JSON.stringify({ success: true, message: "Categoria criada com sucesso!" }),
          { status: 201 },
        );
      }
      throw new Error(`Unexpected request: ${path}`);
    });
    vi.stubGlobal("fetch", fetchMock);
    openRoute("/admin/forum");

    expect(await screen.findByText("Total de tópicos")).toBeTruthy();
    fireEvent.click(screen.getByRole("button", { name: "Categorias" }));
    expect(screen.getByText("Nenhuma categoria cadastrada.")).toBeTruthy();
    fireEvent.change(screen.getByLabelText("Nome"), { target: { value: "Educação" } });
    fireEvent.submit(screen.getByRole("button", { name: "Criar categoria" }).closest("form")!);

    expect(await screen.findByText("Categoria criada com sucesso!")).toBeTruthy();
  });

  it("does not load the admin dashboard for a regular member", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        if (String(input) === "/api/auth/me") {
          return new Response(JSON.stringify(memberSession), { status: 200 });
        }
        throw new Error("A member must not request the admin dashboard.");
      }),
    );
    openRoute("/admin/forum");

    expect(await screen.findByText("Acesso restrito a administradores.")).toBeTruthy();
    expect(screen.queryByRole("heading", { name: "Gerenciar tópicos" })).toBeNull();
  });
});
