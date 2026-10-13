import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const adminSession = {
  authenticated: true,
  user: { id: 1, name: "Admin", username: "admin", isAdmin: true },
  csrfToken: "b".repeat(64),
  flashMessage: null,
};

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  window.history.pushState({}, "", "/");
});

describe("question administration page", () => {
  it("loads question counts and imports a selected JSON file", async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return new Response(JSON.stringify(adminSession), { status: 200 });
      }
      if (path === "/api/admin/questions/stats") {
        return new Response(
          JSON.stringify({
            items: [{ tipo_prova: "sat", total: 12, ultima_atualizacao: null }],
            total: 12,
          }),
          { status: 200 },
        );
      }
      if (path === "/api/admin/questions/import") {
        expect(init?.headers).toEqual({ "x-csrf-token": adminSession.csrfToken });
        return new Response(
          JSON.stringify({
            success: true,
            questoes_inseridas: 1,
            questoes_atualizadas: 0,
            total_processadas: 1,
            message: "Questões carregadas com sucesso! 1 inseridas, 0 atualizadas.",
          }),
          { status: 200 },
        );
      }
      throw new Error(`Unexpected request: ${path}`);
    });
    vi.stubGlobal("fetch", fetchMock);
    window.history.pushState({}, "", "/admin/questoes");
    render(<App />);

    expect(await screen.findByRole("link", { name: /Gerenciar Questões/ })).toBeTruthy();
    expect(await screen.findByText("Total de questões:")).toBeTruthy();
    const file = new File(['{"questoes":[]}'], "questoes.json", { type: "application/json" });
    fireEvent.change(screen.getByLabelText(/Arquivo JSON ou XML/), {
      target: { files: [file] },
    });
    fireEvent.submit(screen.getByRole("button", { name: "IMPORTAR QUESTÕES" }).closest("form")!);

    expect(
      await screen.findByText("Questões carregadas com sucesso! 1 inseridas, 0 atualizadas."),
    ).toBeTruthy();
    const importCall = fetchMock.mock.calls.find(([path]) =>
      String(path).includes("/api/admin/questions/import"),
    );
    expect(importCall?.[1]?.headers).toEqual({ "x-csrf-token": adminSession.csrfToken });
  });

  it("shows the empty state and blocks non-admin users", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        if (String(input) === "/api/auth/me") {
          return new Response(
            JSON.stringify({
              ...adminSession,
              user: { ...adminSession.user, isAdmin: false },
            }),
            { status: 200 },
          );
        }
        throw new Error("A non-admin must not request admin statistics.");
      }),
    );
    window.history.pushState({}, "", "/admin/questoes");
    render(<App />);

    expect(await screen.findByText("Acesso restrito a administradores.")).toBeTruthy();
    expect(screen.queryByRole("heading", { name: "Importar questões" })).toBeNull();
  });
});
