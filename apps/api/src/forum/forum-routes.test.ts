import { createHash } from "node:crypto";
import { afterEach, describe, expect, it, vi } from "vitest";
import type { AuthRepository } from "../auth/auth-repository.js";
import { createApp } from "../app.js";
import type { ForumRepository } from "./forum-repository.js";

const sessionToken = "c".repeat(64);
const csrfToken = "d".repeat(64);
const sessionHash = createHash("sha256").update(sessionToken).digest("hex");

const category = {
  id: 3,
  nome: "Geral",
  descricao: "Discussões",
  cor: "#187bcd",
  icone: "💬",
  ativo: true,
};

const topic = {
  id: 12,
  categoria_id: 3,
  titulo: "Como estudar?",
  conteudo: "Compartilhe dicas.",
  visualizacoes: 2,
  fixado: false,
  fechado: false,
  aprovado: true,
  data_criacao: "2026-10-12T12:00:00.000Z",
  data_atualizacao: "2026-10-12T12:00:00.000Z",
  autor_id: 1,
  autor_nome: "Pessoa",
  categoria_nome: "Geral",
  categoria_cor: "#187bcd",
  categoria_icone: "💬",
  total_respostas: 1,
  total_likes: 2,
};

const reply = {
  id: 7,
  topico_id: 12,
  autor_id: 2,
  autor_nome: "Outra Pessoa",
  conteudo: "Pomodoro ajuda.",
  aprovado: true,
  data_criacao: "2026-10-12T12:10:00.000Z",
  total_likes: 1,
};

function createForumApp(isAdmin = false) {
  const authRepository: AuthRepository = {
    async findUserByUsername() {
      return null;
    },
    async findUserById(id) {
      return {
        id,
        name: isAdmin ? "Admin" : "Pessoa",
        username: isAdmin ? "admin" : "pessoa",
        isAdmin,
        active: true,
      };
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

  const repository: ForumRepository = {
    listCategories: vi.fn(async () => [category]),
    listTopics: vi.fn(async () => [topic]),
    findTopic: vi.fn(async () => topic),
    incrementTopicViews: vi.fn(async () => {}),
    listReplies: vi.fn(async () => [reply]),
    createTopic: vi.fn(async () => {}),
    createReply: vi.fn(async () => {}),
    notifyReply: vi.fn(async () => {}),
    createLike: vi.fn(async () => {}),
    moderateTopic: vi.fn(async () => true),
    moderateReply: vi.fn(async () => true),
    getAdminStats: vi.fn(async () => ({
      total_topicos: 1,
      topicos_pendentes: 0,
      total_respostas: 1,
      respostas_pendentes: 0,
      total_usuarios: 2,
    })),
    listPendingReplies: vi.fn(async () => []),
    listAdminUsers: vi.fn(async () => []),
    listModerationLogs: vi.fn(async () => []),
    createCategory: vi.fn(async () => {}),
    changeUserState: vi.fn(async (moderatorId, targetUserId) => moderatorId !== targetUserId),
  };
  const auth = {
    repository: authRepository,
    sessionTtlSeconds: 3600,
    cookieSecure: false,
    webOrigin: "http://localhost:5173",
  };
  const app = createApp({
    auth,
    forum: { auth, repository },
  });
  const headers = { cookie: `daydreaming_node_sid=${sessionToken}` };
  const mutationHeaders = { ...headers, "x-csrf-token": csrfToken };
  return { app, repository, headers, mutationHeaders };
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe("forum routes", () => {
  it("protects forum reads and returns categories, topics, and details to members", async () => {
    const { app, repository, headers } = createForumApp();
    const denied = await app.inject({ method: "GET", url: "/api/forum/topics" });
    expect(denied.statusCode).toBe(401);

    const categories = await app.inject({
      method: "GET",
      url: "/api/forum/categories",
      headers,
    });
    expect(categories.statusCode).toBe(200);
    expect(categories.json().items).toEqual([category]);

    const topics = await app.inject({
      method: "GET",
      url: "/api/forum/topics?busca=estudar",
      headers,
    });
    expect(topics.statusCode).toBe(200);
    expect(topics.json().items).toEqual([topic]);
    expect(repository.listTopics).toHaveBeenCalledWith({
      search: "estudar",
      admin: false,
      limit: 20,
    });

    const detail = await app.inject({
      method: "GET",
      url: "/api/forum/topics/12",
      headers,
    });
    expect(detail.statusCode).toBe(200);
    expect(detail.json()).toEqual({ topic, replies: [reply] });
    expect(repository.incrementTopicViews).toHaveBeenCalledWith(12);
    await app.close();
  });

  it("requires CSRF on topic creation and preserves server-side author identity", async () => {
    const { app, repository, headers, mutationHeaders } = createForumApp();
    const denied = await app.inject({
      method: "POST",
      url: "/api/forum/topics",
      headers,
      payload: { categoria_id: 3, titulo: "Nova conversa", conteudo: "Texto." },
    });
    expect(denied.statusCode).toBe(403);

    const created = await app.inject({
      method: "POST",
      url: "/api/forum/topics",
      headers: mutationHeaders,
      payload: { categoria_id: 3, titulo: "  Nova conversa ", conteudo: " Texto. " },
    });
    expect(created.statusCode).toBe(201);
    expect(created.json()).toEqual({
      success: true,
      message: "Tópico criado e publicado com sucesso!",
    });
    expect(repository.createTopic).toHaveBeenCalledWith(3, 1, "Nova conversa", "Texto.");

    const invalid = await app.inject({
      method: "POST",
      url: "/api/forum/topics",
      headers: mutationHeaders,
      payload: { categoria_id: 3, titulo: "0", conteudo: "texto" },
    });
    expect(invalid.statusCode).toBe(400);
    await app.close();
  });

  it("rejects non-admin dashboard access and lets admins moderate topics", async () => {
    const member = createForumApp(false);
    const denied = await member.app.inject({
      method: "GET",
      url: "/api/admin/forum/dashboard",
      headers: member.headers,
    });
    expect(denied.statusCode).toBe(403);
    await member.app.close();

    const admin = createForumApp(true);
    const dashboard = await admin.app.inject({
      method: "GET",
      url: "/api/admin/forum/dashboard?filtro=pendentes",
      headers: admin.headers,
    });
    expect(dashboard.statusCode).toBe(200);
    expect(dashboard.json().stats.total_topicos).toBe(1);

    const moderated = await admin.app.inject({
      method: "POST",
      url: "/api/admin/forum/moderation",
      headers: admin.mutationHeaders,
      payload: { tipo: "topico", id: 12, acao: "fechar", motivo: "Concluído" },
    });
    expect(moderated.statusCode).toBe(200);
    expect(moderated.json().message).toBe("Status de fechamento alterado!");
    expect(admin.repository.moderateTopic).toHaveBeenCalledWith(1, 12, "fechar", "Concluído");
    await admin.app.close();
  });

  it("prevents administrators from changing their own account", async () => {
    const { app } = createForumApp(true);
    const response = await app.inject({
      method: "POST",
      url: "/api/admin/forum/users/action",
      headers: {
        cookie: `daydreaming_node_sid=${sessionToken}`,
        "x-csrf-token": csrfToken,
      },
      payload: { usuario_id: 1, acao: "rebaixar" },
    });
    expect(response.statusCode).toBe(400);
    expect(response.json()).toEqual({ message: "Não é possível alterar esse usuário." });
    await app.close();
  });
});
