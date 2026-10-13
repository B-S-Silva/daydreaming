import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "../../App.js";

const memberSession = {
  authenticated: true,
  user: { id: 2, name: "Membro", username: "membro", isAdmin: false },
  csrfToken: "f".repeat(64),
  flashMessage: null,
};

const result = {
  sessao_id: 9,
  tipo_prova: "sat",
  nome: "SAT",
  cor: "#FF9800",
  pontuacao: 85.5,
  acertos: 10,
  total_questoes: 120,
  questoes_respondidas: 12,
  tempo_gasto: 360,
};

const testHistory = {
  items: [
    {
      sessao_id: 9,
      tipo_prova: "sat",
      nome: "SAT",
      cor: "#FF9800",
      icone: "🎓",
      data_realizacao: "2025-01-02T10:00:00.000Z",
      acertos: 10,
      total_questoes: 120,
      tempo_gasto: 360,
      pontuacao: 85.5,
    },
  ],
  stats: { total_testes: 1, media_geral: 85.5, melhor_pontuacao: 85.5, testes_aprovados: 1 },
  by_type: [],
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

describe("results pages", () => {
  it("shows the result summary and review link", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        if (String(input) === "/api/auth/me") {
          return new Response(JSON.stringify(memberSession), { status: 200 });
        }
        if (String(input) === "/api/results/9") {
          return new Response(JSON.stringify(result), { status: 200 });
        }
        throw new Error(`Unexpected request: ${String(input)}`);
      }),
    );
    openRoute("/resultados/9");

    expect(await screen.findByText("85.5%")).toBeTruthy();
    expect(screen.getByRole("link", { name: /Revisar prova/ }).getAttribute("href")).toBe(
      "/resultados/9/revisao",
    );
  });

  it("shows the review status and correct answer", async () => {
    const review = {
      session: {
        sessao_id: 9,
        tipo_prova: "sat",
        nome: "SAT",
        cor: "#FF9800",
        inicio: "2025-01-02T10:00:00.000Z",
        pontuacao: 85.5,
        acertos: 10,
        questoes_respondidas: 12,
        tempo_gasto: 360,
      },
      questions: [
        {
          id: 101,
          numero_questao: 1,
          enunciado: "Qual é a resposta?",
          tipo_questao: "multipla_escolha",
          alternativas: { a: "Correta", b: "Incorreta" },
          resposta_correta: "a",
          resposta_dissertativa: null,
          materia: "Matemática",
          assunto: "Álgebra",
          resposta_usuario: "a",
          resposta_dissertativa_usuario: null,
          esta_correta: true,
        },
        {
          id: 102,
          numero_questao: 2,
          enunciado: "Explique a resposta.",
          tipo_questao: "dissertativa",
          alternativas: {},
          resposta_correta: "Resposta de fallback",
          resposta_dissertativa: "0",
          materia: "Matemática",
          assunto: "Explicação",
          resposta_usuario: null,
          resposta_dissertativa_usuario: "0",
          esta_correta: false,
        },
      ],
    };
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input: RequestInfo | URL) => {
        if (String(input) === "/api/auth/me") {
          return new Response(JSON.stringify(memberSession), { status: 200 });
        }
        if (String(input) === "/api/results/9/review") {
          return new Response(JSON.stringify(review), { status: 200 });
        }
        throw new Error(`Unexpected request: ${String(input)}`);
      }),
    );
    openRoute("/resultados/9/revisao");

    expect(await screen.findByText("Qual é a resposta?")).toBeTruthy();
    expect(screen.getByText("Correta", { selector: ".review-status" })).toBeTruthy();
    expect(screen.getByText(/Resposta correta/, { selector: "small" })).toBeTruthy();
    expect(screen.getByText("Não Respondida")).toBeTruthy();
    expect(screen.getByText("Resposta de fallback")).toBeTruthy();
    expect(screen.getByText("Questão não respondida")).toBeTruthy();
  });

  it("shows history and preserves selected PHP filter parameters without filtering records", async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
      if (String(input) === "/api/auth/me") {
        return new Response(JSON.stringify(memberSession), { status: 200 });
      }
      if (String(input) === "/api/results/history/tests") {
        return new Response(JSON.stringify(testHistory), { status: 200 });
      }
      throw new Error(`Unexpected request: ${String(input)}`);
    });
    vi.stubGlobal("fetch", fetchMock);
    openRoute("/historico/testes");

    expect(await screen.findByRole("link", { name: /SAT/ })).toBeTruthy();
    fireEvent.change(screen.getByLabelText("Tipo de Prova:"), { target: { value: "sat" } });
    fireEvent.click(screen.getByRole("button", { name: /Filtrar/ }));

    expect(await screen.findByRole("link", { name: /85.5%/ })).toBeTruthy();
    expect(window.location.search).toContain("tipo=sat");
    expect(fetchMock).toHaveBeenCalledWith("/api/results/history/tests", {
      credentials: "same-origin",
    });
  });
});
