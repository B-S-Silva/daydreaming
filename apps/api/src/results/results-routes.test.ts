import { createHash } from "node:crypto";
import { afterEach, describe, expect, it, vi } from "vitest";
import type { AuthRepository } from "../auth/auth-repository.js";
import { createApp } from "../app.js";
import type { ResultsRepository } from "./results-repository.js";

const sessionToken = "a".repeat(64);
const csrfToken = "b".repeat(64);
const sessionHash = createHash("sha256").update(sessionToken).digest("hex");

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
  ],
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
  by_type: [
    {
      tipo_prova: "sat",
      nome: "SAT",
      cor: "#FF9800",
      icone: "🎓",
      media_pontuacao: 85.5,
      testes_por_tipo: 1,
    },
  ],
};

const sessionHistory = {
  items: [
    {
      sessao_id: 9,
      tipo_prova: "sat",
      nome: "SAT",
      cor: "#FF9800",
      inicio: "2025-01-02T10:00:00.000Z",
      fim: "2025-01-02T10:06:00.000Z",
      status: "finalizado",
      pontuacao_final: 85.5,
      acertos: 10,
      questoes_respondidas: 12,
      tempo_gasto: 360,
      duracao_minutos: 180,
      total_questoes: 120,
      pontuacao_calculada: 8.3333333333,
    },
  ],
  stats: {
    total_provas: 1,
    questoes_respondidas: 12,
    acertos: 10,
    tempo_total: 360,
    media_pontuacao: 8.3333333333,
  },
};

function createResultsApp() {
  const authRepository: AuthRepository = {
    async findUserByUsername() {
      return null;
    },
    async findUserById(id) {
      return { id, name: "Membro", username: "membro", isAdmin: false, active: true };
    },
    async findDuplicateField() {
      return null;
    },
    async createUser() {
      return 2;
    },
    async recordLoginAttempt() {},
    async updateLastAccess() {},
    async recordLogout() {},
    async findSession(idHash) {
      return idHash === sessionHash ? { userId: 1, csrfToken, flashMessage: null } : null;
    },
    async createSession() {},
    async setFlashMessage() {},
    async consumeFlashMessage() {
      return null;
    },
    async deleteSession() {},
    async deleteExpiredSessions() {},
  };
  const repository: ResultsRepository = {
    findResult: vi.fn(async (userId, id) => (userId === 1 && id === 9 ? result : null)),
    findReview: vi.fn(async (userId, id) => (userId === 1 && id === 9 ? review : null)),
    getTestHistory: vi.fn(async () => testHistory),
    getSessionHistory: vi.fn(async () => sessionHistory),
  };
  const app = createApp({
    auth: {
      repository: authRepository,
      sessionTtlSeconds: 3600,
      cookieSecure: false,
      webOrigin: "http://localhost:5173",
    },
    results: {
      auth: {
        repository: authRepository,
        sessionTtlSeconds: 3600,
        cookieSecure: false,
        webOrigin: "http://localhost:5173",
      },
      repository,
    },
  });
  return {
    app,
    repository,
    headers: { cookie: `daydreaming_node_sid=${sessionToken}` },
  };
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe("results routes", () => {
  it("requires login and returns a result only for its owner", async () => {
    const { app, repository, headers } = createResultsApp();
    expect((await app.inject({ method: "GET", url: "/api/results/9" })).statusCode).toBe(401);

    const response = await app.inject({ method: "GET", url: "/api/results/9", headers });
    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual(result);
    expect(repository.findResult).toHaveBeenCalledWith(1, 9);

    const missing = await app.inject({ method: "GET", url: "/api/results/10", headers });
    expect(missing.statusCode).toBe(404);
    await app.close();
  });

  it("returns review and both history contracts", async () => {
    const { app, headers } = createResultsApp();
    const reviewResponse = await app.inject({
      method: "GET",
      url: "/api/results/9/review",
      headers,
    });
    expect(reviewResponse.statusCode).toBe(200);
    expect(reviewResponse.json()).toEqual(review);

    const testsResponse = await app.inject({
      method: "GET",
      url: "/api/results/history/tests",
      headers,
    });
    expect(testsResponse.statusCode).toBe(200);
    expect(testsResponse.json()).toEqual(testHistory);

    const sessionsResponse = await app.inject({
      method: "GET",
      url: "/api/results/history/sessions",
      headers,
    });
    expect(sessionsResponse.statusCode).toBe(200);
    expect(sessionsResponse.json()).toEqual(sessionHistory);
    await app.close();
  });

  it("rejects invalid session identifiers without querying the repository", async () => {
    const { app, repository, headers } = createResultsApp();
    const response = await app.inject({ method: "GET", url: "/api/results/0", headers });
    expect(response.statusCode).toBe(404);
    expect(repository.findResult).not.toHaveBeenCalled();
    await app.close();
  });
});
