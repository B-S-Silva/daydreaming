import { createHash } from "node:crypto";
import { afterEach, describe, expect, it, vi } from "vitest";
import type { AuthRepository } from "../auth/auth-repository.js";
import { createApp } from "../app.js";
import type { QuestionRepository } from "./question-repository.js";

const sessionToken = "a".repeat(64);
const csrfToken = "b".repeat(64);
const sessionHash = createHash("sha256").update(sessionToken).digest("hex");

function createRepositories(isAdmin = true) {
  const authRepository: AuthRepository = {
    async findUserByUsername() {
      return null;
    },
    async findUserById(id) {
      return {
        id,
        name: "Admin",
        username: "admin",
        isAdmin,
        active: true,
      };
    },
    async findDuplicateField() {
      return null;
    },
    async createUser() {
      return 1;
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

  const questionRepository: QuestionRepository = {
    listStats: vi.fn(async () => [
      { tipo_prova: "sat" as const, total: 12, ultima_atualizacao: "2025-01-01 12:00:00" },
    ]),
    importQuestions: vi.fn(async (_examType, questions) => ({
      inserted: questions.length,
      updated: 0,
    })),
  };

  const app = createApp({
    auth: {
      repository: authRepository,
      sessionTtlSeconds: 3600,
      cookieSecure: false,
      webOrigin: "http://localhost:5173",
    },
    questions: {
      auth: {
        repository: authRepository,
        sessionTtlSeconds: 3600,
        cookieSecure: false,
        webOrigin: "http://localhost:5173",
      },
      repository: questionRepository,
    },
  });

  return { app, questionRepository };
}

const question = {
  numero_questao: 1,
  enunciado: "Question",
  alternativa_a: "A",
  alternativa_b: "B",
  alternativa_c: "C",
  alternativa_d: "D",
  alternativa_e: "E",
  resposta_correta: "a",
};

function multipartRequest(filename: string, document: string) {
  const boundary = "question-import-test";
  const payload = [
    `--${boundary}`,
    'Content-Disposition: form-data; name="tipo_prova"',
    "",
    "sat",
    `--${boundary}`,
    `Content-Disposition: form-data; name="arquivo_questoes"; filename="${filename}"`,
    "Content-Type: application/json",
    "",
    document,
    `--${boundary}--`,
    "",
  ].join("\r\n");

  return {
    payload,
    headers: {
      "content-type": `multipart/form-data; boundary=${boundary}`,
      cookie: `daydreaming_node_sid=${sessionToken}`,
      "x-csrf-token": csrfToken,
    },
  };
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe("question administration routes", () => {
  it("requires an administrator to read question statistics", async () => {
    const { app, questionRepository } = createRepositories(false);
    const response = await app.inject({
      method: "GET",
      url: "/api/admin/questions/stats",
      headers: { cookie: `daydreaming_node_sid=${sessionToken}` },
    });

    expect(response.statusCode).toBe(403);
    expect(response.json()).toEqual({ message: "Acesso restrito a administradores." });
    expect(questionRepository.listStats).not.toHaveBeenCalled();
    await app.close();
  });

  it("returns stats to an administrator", async () => {
    const { app } = createRepositories();
    const response = await app.inject({
      method: "GET",
      url: "/api/admin/questions/stats",
      headers: { cookie: `daydreaming_node_sid=${sessionToken}` },
    });

    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual({
      items: [{ tipo_prova: "sat", total: 12, ultima_atualizacao: "2025-01-01 12:00:00" }],
      total: 12,
    });
    await app.close();
  });

  it("imports JSON with the shared response format and requires CSRF", async () => {
    const { app, questionRepository } = createRepositories();
    const denied = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: { cookie: `daydreaming_node_sid=${sessionToken}` },
    });
    expect(denied.statusCode).toBe(403);

    const multipart = multipartRequest("questoes.json", JSON.stringify({ questoes: [question] }));
    const response = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: multipart.headers,
      payload: multipart.payload,
    });

    expect(response.statusCode).toBe(200);
    expect(response.json()).toMatchObject({
      success: true,
      questoes_inseridas: 1,
      questoes_atualizadas: 0,
      total_processadas: 1,
    });
    expect(questionRepository.importQuestions).toHaveBeenCalledWith("sat", [
      expect.objectContaining({ numero_questao: 1, dificuldade: "medio" }),
    ]);
    await app.close();
  });

  it("rejects malformed JSON and unsupported upload extensions", async () => {
    const { app, questionRepository } = createRepositories();
    const malformed = multipartRequest("questoes.json", "{");
    const malformedResponse = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: malformed.headers,
      payload: malformed.payload,
    });
    expect(malformedResponse.statusCode).toBe(400);
    expect(malformedResponse.json()).toEqual({ message: "Arquivo inválido: JSON inválido." });

    const unsupported = multipartRequest("questoes.csv", JSON.stringify({ questoes: [question] }));
    const unsupportedResponse = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: unsupported.headers,
      payload: unsupported.payload,
    });
    expect(unsupportedResponse.statusCode).toBe(400);
    expect(unsupportedResponse.json()).toEqual({
      message: "Apenas arquivos JSON e XML são permitidos.",
    });
    expect(questionRepository.importQuestions).not.toHaveBeenCalled();
    await app.close();
  });

  it("preserves PHP empty-value validation for required question fields", async () => {
    const { app, questionRepository } = createRepositories();
    const multipart = multipartRequest(
      "questoes.json",
      JSON.stringify({ questoes: [{ ...question, enunciado: "0" }] }),
    );
    const response = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: multipart.headers,
      payload: multipart.payload,
    });

    expect(response.statusCode).toBe(400);
    expect(response.json()).toEqual({
      message: "Arquivo inválido: Campo obrigatório ausente: enunciado",
    });
    expect(questionRepository.importQuestions).not.toHaveBeenCalled();
    await app.close();
  });

  it("imports XML questions and normalizes numeric question identifiers", async () => {
    const { app, questionRepository } = createRepositories();
    const xml = `<questoes><questao><numero_questao>7</numero_questao><enunciado>Question</enunciado><alternativa_a>A</alternativa_a><alternativa_b>B</alternativa_b><alternativa_c>C</alternativa_c><alternativa_d>D</alternativa_d><alternativa_e>E</alternativa_e><resposta_correta>a</resposta_correta></questao></questoes>`;
    const multipart = multipartRequest("questoes.xml", xml);
    const response = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: multipart.headers,
      payload: multipart.payload,
    });

    expect(response.statusCode).toBe(200);
    expect(questionRepository.importQuestions).toHaveBeenCalledWith("sat", [
      expect.objectContaining({ numero_questao: 7 }),
    ]);
    await app.close();
  });

  it("rejects uploads larger than 10 MB", async () => {
    const { app, questionRepository } = createRepositories();
    const multipart = multipartRequest("questoes.json", "x".repeat(10 * 1024 * 1024 + 1));
    const response = await app.inject({
      method: "POST",
      url: "/api/admin/questions/import",
      headers: multipart.headers,
      payload: multipart.payload,
    });

    expect(response.statusCode).toBe(413);
    expect(response.json()).toEqual({ message: "O arquivo excede o limite máximo de 10MB." });
    expect(questionRepository.importQuestions).not.toHaveBeenCalled();
    await app.close();
  });
});
