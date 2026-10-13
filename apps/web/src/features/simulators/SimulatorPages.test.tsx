import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const memberSession = {
  authenticated: true,
  user: { id: 2, name: "Membro", username: "membro", isAdmin: false },
  csrfToken: "f".repeat(64),
  flashMessage: null,
};

const dashboard = {
  exams: [
    {
      tipo_prova: "sat",
      nome: "SAT",
      descricao: "Scholastic Assessment Test",
      icone: "🎓",
      cor: "#FF9800",
      duracao: "3h00min",
      questoes: 120,
      duracao_minutos: 180,
      questoes_total: 120,
    },
  ],
  stats: { total_testes: 3, media_pontuacao: 76.5 },
};

const activeSession = {
  sessao_id: 9,
  tipo_prova: "sat",
  nome: "SAT",
  cor: "#FF9800",
  status: "ativo",
  tempo_restante: 3600,
  questoes_total: 1,
  questoes: [
    {
      id: 101,
      numero: 1,
      enunciado: "Qual é a resposta?",
      tipo_questao: "multipla_escolha",
      alternativas: { a: "Correta", b: "Incorreta" },
      dificuldade: "medio",
      materia: "Matemática",
      assunto: "Álgebra",
      explicacao: null,
    },
  ],
};

function openRoute(path: string) {
  window.history.pushState({}, "", path);
  render(<App />);
}

afterEach(() => {
  cleanup();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
  window.history.pushState({}, "", "/");
});

describe("simulator pages", () => {
  it("shows exam cards and user statistics", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        if (String(input) === "/api/simulator/dashboard") {
          return new Response(JSON.stringify(dashboard), { status: 200 });
        }
        throw new Error(`Unexpected request: ${String(input)}`);
      }),
    );
    openRoute("/simulador");

    expect(await screen.findByRole("heading", { name: "SAT" })).toBeTruthy();
    expect(screen.getByText("76.5%")).toBeTruthy();
    expect(screen.getByRole("link", { name: "Iniciar simulado" })).toBeTruthy();
  });

  it("saves a selected answer with CSRF and shows the final score", async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const path = String(input);
      if (path === "/api/auth/me") {
        return new Response(JSON.stringify(memberSession), { status: 200 });
      }
      if (path === "/api/simulator/sessions/9") {
        return new Response(JSON.stringify(activeSession), { status: 200 });
      }
      if (path === "/api/simulator/answers") {
        expect(init?.headers).toEqual({
          "content-type": "application/json",
          "x-csrf-token": memberSession.csrfToken,
        });
        return new Response(
          JSON.stringify({
            success: true,
            esta_correta: true,
            tipo_resposta: "multipla_escolha",
            questao_numero: 1,
            message: "Resposta salva com sucesso",
          }),
          { status: 200 },
        );
      }
      if (path === "/api/simulator/finish") {
        return new Response(
          JSON.stringify({
            sucesso: true,
            pontuacao: 100,
            acertos: 1,
            questoes_respondidas: 1,
            tempo_gasto: 60,
          }),
          { status: 200 },
        );
      }
      throw new Error(`Unexpected request: ${path}`);
    });
    vi.stubGlobal("fetch", fetchMock);
    vi.stubGlobal(
      "confirm",
      vi.fn(() => true),
    );
    openRoute("/simulador/sessoes/9");

    fireEvent.click(await screen.findByRole("radio", { name: /Correta/ }));
    expect(await screen.findByRole("button", { name: /Finalizar teste/ })).toBeTruthy();
    fireEvent.click(screen.getByRole("button", { name: /Finalizar teste/ }));

    expect(await screen.findByText("Pontuação: 100.0%")).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/api/simulator/finish",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          sessao_id: 9,
          finalizar: "1",
          respostas: { "1": "a" },
        }),
      }),
    );
  });
});
