import { createHash } from "node:crypto";
import { afterEach, describe, expect, it, vi } from "vitest";
import type { AuthRepository } from "../auth/auth-repository.js";
import { createApp } from "../app.js";
import type { SimulatorQuestionRecord, SimulatorRepository } from "./simulator-service.js";

const sessionToken = "a".repeat(64);
const csrfToken = "b".repeat(64);
const sessionHash = createHash("sha256").update(sessionToken).digest("hex");

const multipleChoiceQuestion: SimulatorQuestionRecord = {
  id: 101,
  numero: 1,
  enunciado: "Qual é a resposta?",
  tipoQuestao: "multipla_escolha",
  alternativaA: "Correta",
  alternativaB: "Incorreta",
  alternativaC: null,
  alternativaD: null,
  alternativaE: null,
  respostaCorreta: "a",
  respostaDissertativa: null,
  dificuldade: "medio",
  materia: "Matemática",
  assunto: "Álgebra",
  explicacao: null,
};

function createSimulatorApp(question = multipleChoiceQuestion) {
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

  const repository: SimulatorRepository = {
    getUserStats: vi.fn(async () => ({ total_testes: 0, media_pontuacao: null })),
    createSession: vi.fn(async () => 9),
    findSession: vi.fn(async (userId, id) =>
      userId === 1 && id === 9
        ? {
            id: 9,
            tipoProva: "sat" as const,
            status: "ativo" as const,
            duracaoMinutos: 180,
            tempoDecorrido: 45,
          }
        : null,
    ),
    listQuestions: vi.fn(async () => [question]),
    findQuestion: vi.fn(async () => question),
    saveAnswer: vi.fn(async () => {}),
    expireSession: vi.fn(async () => {}),
    finalizeSession: vi.fn(async () => 60),
  };
  const auth = {
    repository: authRepository,
    sessionTtlSeconds: 3600,
    cookieSecure: false,
    webOrigin: "http://localhost:5173",
  };
  const app = createApp({ auth, simulator: { auth, repository } });
  const headers = { cookie: `daydreaming_node_sid=${sessionToken}` };
  const mutationHeaders = { ...headers, "x-csrf-token": csrfToken };
  return { app, repository, headers, mutationHeaders };
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe("simulator routes", () => {
  it("requires an authenticated session for the dashboard and returns exam statistics", async () => {
    const { app, headers } = createSimulatorApp();
    const denied = await app.inject({ method: "GET", url: "/api/simulator/dashboard" });
    expect(denied.statusCode).toBe(401);

    const response = await app.inject({
      method: "GET",
      url: "/api/simulator/dashboard",
      headers,
    });
    expect(response.statusCode).toBe(200);
    expect(response.json().stats).toEqual({ total_testes: 0, media_pontuacao: null });
    expect(response.json().exams).toHaveLength(9);
    await app.close();
  });

  it("requires CSRF when starting and uses the exam's PHP duration", async () => {
    const { app, repository, headers, mutationHeaders } = createSimulatorApp();
    const denied = await app.inject({
      method: "POST",
      url: "/api/simulator/sessions",
      headers,
      payload: { tipo_prova: "sat" },
    });
    expect(denied.statusCode).toBe(403);

    const response = await app.inject({
      method: "POST",
      url: "/api/simulator/sessions",
      headers: mutationHeaders,
      payload: { tipo_prova: "sat" },
    });
    expect(response.statusCode).toBe(201);
    expect(response.json()).toEqual({ sessao_id: 9, tipo_prova: "sat" });
    expect(repository.createSession).toHaveBeenCalledWith(1, "sat", 180);
    await app.close();
  });

  it("returns questions without the multiple-choice answer key", async () => {
    const { app, repository, headers } = createSimulatorApp();
    const response = await app.inject({
      method: "GET",
      url: "/api/simulator/sessions/9",
      headers,
    });
    expect(response.statusCode).toBe(200);
    expect(response.json().questoes[0]).toEqual({
      id: 101,
      numero: 1,
      enunciado: "Qual é a resposta?",
      tipo_questao: "multipla_escolha",
      alternativas: { a: "Correta", b: "Incorreta" },
      dificuldade: "medio",
      materia: "Matemática",
      assunto: "Álgebra",
      explicacao: null,
    });
    expect(JSON.stringify(response.json())).not.toContain('"resposta_correta"');
    expect(repository.listQuestions).toHaveBeenCalledWith("sat", 120);
    await app.close();
  });

  it("saves an answer using the authenticated user and verifies its correctness", async () => {
    const { app, repository, mutationHeaders } = createSimulatorApp();
    const response = await app.inject({
      method: "POST",
      url: "/api/simulator/answers",
      headers: mutationHeaders,
      payload: {
        sessao_id: 9,
        questao_numero: 1,
        resposta: "A",
        tipo_resposta: "multipla_escolha",
      },
    });
    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual({
      success: true,
      esta_correta: true,
      tipo_resposta: "multipla_escolha",
      questao_numero: 1,
      message: "Resposta salva com sucesso",
    });
    expect(repository.saveAnswer).toHaveBeenCalledWith(
      1,
      9,
      multipleChoiceQuestion,
      "A",
      "multipla_escolha",
      true,
      "127.0.0.1",
    );
    await app.close();
  });

  it("matches PHP's ASCII-only lowercasing for accented answers", async () => {
    const accentedQuestion = { ...multipleChoiceQuestion, respostaCorreta: "Á" };
    const { app, mutationHeaders } = createSimulatorApp(accentedQuestion);
    const response = await app.inject({
      method: "POST",
      url: "/api/simulator/answers",
      headers: mutationHeaders,
      payload: {
        sessao_id: 9,
        questao_numero: 1,
        resposta: "á",
        tipo_resposta: "multipla_escolha",
      },
    });
    expect(response.statusCode).toBe(200);
    expect(response.json().esta_correta).toBe(false);
    await app.close();
  });

  it("finalizes with exact-answer scoring and preserves the PHP result response shape", async () => {
    const essayQuestion = {
      ...multipleChoiceQuestion,
      tipoQuestao: "dissertativa",
      respostaDissertativa: "42",
    };
    const { app, repository, mutationHeaders } = createSimulatorApp(essayQuestion);
    const response = await app.inject({
      method: "POST",
      url: "/api/simulator/finish",
      headers: mutationHeaders,
      payload: {
        sessao_id: 9,
        finalizar: "1",
        respostas: { "1": "A resposta é 42" },
      },
    });
    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual({
      sucesso: true,
      pontuacao: 0,
      acertos: 0,
      questoes_respondidas: 1,
      tempo_gasto: 60,
    });
    expect(repository.finalizeSession).toHaveBeenCalledWith(
      1,
      9,
      [{ question: essayQuestion, answer: "A resposta é 42", isCorrect: false }],
      { score: 0, correct: 0, answered: 1 },
    );
    await app.close();
  });
});
