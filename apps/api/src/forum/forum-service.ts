import {
  ForumCategoryInputSchema,
  ForumLikeInputSchema,
  ForumReplyActionSchema,
  ForumReplyInputSchema,
  ForumTopicActionSchema,
  ForumTopicInputSchema,
} from "@daydreaming/shared";
import type {
  ForumAdminUser,
  ForumCategory,
  ForumModerationLog,
  ForumReply,
  ForumTopic,
} from "@daydreaming/shared";
import type { ForumRepository } from "./forum-repository.js";

export interface ForumTopicFilter {
  categoryId?: number;
  search?: string;
  moderationFilter?: "todos" | "pendentes" | "fixados" | "fechados";
  admin: boolean;
  limit: number;
}

export class ForumValidationError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "ForumValidationError";
  }
}

const topicActionMessages: Record<string, string> = {
  aprovar: "Tópico aprovado com sucesso!",
  rejeitar: "Tópico rejeitado!",
  fixar: "Status de fixação alterado!",
  desfixar: "Tópico desfixado com sucesso!",
  fechar: "Status de fechamento alterado!",
  abrir: "Tópico reaberto com sucesso!",
  deletar: "Tópico deletado!",
};

const replyActionMessages: Record<string, string> = {
  aprovar: "Resposta aprovada com sucesso!",
  rejeitar: "Resposta rejeitada!",
  deletar: "Resposta deletada!",
};

const userActionMessages: Record<string, string> = {
  promover: "Usuário promovido a administrador com sucesso!",
  rebaixar: "Usuário rebaixado para usuário comum com sucesso!",
  banir: "Usuário banido com sucesso!",
  desbanir: "Usuário desbloqueado com sucesso!",
};

function positiveQueryInteger(value: unknown, fieldName: string): number | undefined {
  if (value === undefined || value === "") {
    return undefined;
  }
  if (typeof value !== "string" || !/^[1-9]\d*$/.test(value)) {
    throw new ForumValidationError(`${fieldName} inválido.`);
  }
  const number = Number(value);
  if (!Number.isSafeInteger(number)) {
    throw new ForumValidationError(`${fieldName} inválido.`);
  }
  return number;
}

export class ForumService {
  constructor(private readonly repository: ForumRepository) {}

  async listCategories(activeOnly = true): Promise<ForumCategory[]> {
    return this.repository.listCategories(activeOnly);
  }

  async listTopics(query: unknown, admin = false): Promise<ForumTopic[]> {
    const values =
      typeof query === "object" && query !== null ? (query as Record<string, unknown>) : {};
    const categoryId = positiveQueryInteger(values.categoria, "Categoria");
    const moderationFilter = values.filtro;
    if (
      admin &&
      moderationFilter !== undefined &&
      moderationFilter !== "todos" &&
      moderationFilter !== "pendentes" &&
      moderationFilter !== "fixados" &&
      moderationFilter !== "fechados"
    ) {
      throw new ForumValidationError("Filtro inválido.");
    }
    const search = typeof values.busca === "string" ? values.busca : undefined;
    const selectedFilter = admin
      ? (moderationFilter as ForumTopicFilter["moderationFilter"])
      : undefined;
    return this.repository.listTopics({
      ...(categoryId === undefined ? {} : { categoryId }),
      ...(search === undefined ? {} : { search }),
      ...(selectedFilter === undefined ? {} : { moderationFilter: selectedFilter }),
      admin,
      limit: admin ? 50 : 20,
    });
  }

  async readTopic(rawId: unknown): Promise<{ topic: ForumTopic; replies: ForumReply[] } | null> {
    const id = positiveQueryInteger(String(rawId), "Tópico");
    if (!id) {
      throw new ForumValidationError("Tópico inválido.");
    }
    await this.repository.incrementTopicViews(id);
    const topic = await this.repository.findTopic(id);
    if (!topic) {
      return null;
    }
    return { topic, replies: await this.repository.listReplies(id) };
  }

  async createTopic(userId: number, rawInput: unknown): Promise<string> {
    const parsed = ForumTopicInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new ForumValidationError(
        parsed.error.issues[0]?.path[0] === "categoria_id"
          ? "Categoria inválida."
          : "Preencha o título e o conteúdo do tópico.",
      );
    }
    await this.repository.createTopic(
      parsed.data.categoria_id,
      userId,
      parsed.data.titulo,
      parsed.data.conteudo,
    );
    return "Tópico criado e publicado com sucesso!";
  }

  async createReply(userId: number, rawInput: unknown): Promise<string> {
    const parsed = ForumReplyInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new ForumValidationError("Digite o conteúdo da resposta.");
    }
    await this.repository.createReply(parsed.data.topico_id, userId, parsed.data.conteudo);
    return "Resposta adicionada com sucesso!";
  }

  async like(userId: number, rawInput: unknown): Promise<void> {
    const parsed = ForumLikeInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new ForumValidationError("Curtida inválida.");
    }
    await this.repository.createLike(userId, parsed.data.tipo, parsed.data.id);
  }

  async adminDashboard(query: unknown) {
    const [stats, topics, pendingReplies, categories, users, logs] = await Promise.all([
      this.repository.getAdminStats(),
      this.listTopics(query, true),
      this.repository.listPendingReplies(),
      this.repository.listCategories(false),
      this.repository.listAdminUsers(),
      this.repository.listModerationLogs(),
    ]);
    return { stats, topics, pendingReplies, categories, users, logs };
  }

  async createCategory(rawInput: unknown): Promise<string> {
    const parsed = ForumCategoryInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new ForumValidationError("Informe um nome e uma cor válida para a categoria.");
    }
    await this.repository.createCategory(parsed.data);
    return "Categoria criada com sucesso!";
  }

  async moderate(
    moderatorId: number,
    rawInput: unknown,
  ): Promise<{ message: string; found: boolean }> {
    if (typeof rawInput !== "object" || rawInput === null) {
      throw new ForumValidationError("Ação de moderação inválida.");
    }
    const input = rawInput as Record<string, unknown>;
    const id = positiveQueryInteger(String(input.id ?? ""), "Conteúdo");
    const action = input.acao;
    const reason = typeof input.motivo === "string" ? input.motivo : null;
    if (!id) {
      throw new ForumValidationError("Conteúdo inválido.");
    }
    if (input.tipo === "topico") {
      const supported = ["aprovar", "rejeitar", "fixar", "desfixar", "fechar", "abrir", "deletar"];
      if (typeof action !== "string" || !supported.includes(action)) {
        throw new ForumValidationError("Ação de moderação inválida.");
      }
      if (
        ["aprovar", "rejeitar", "deletar"].includes(action) &&
        !ForumTopicActionSchema.safeParse(action).success
      ) {
        throw new ForumValidationError("Ação de moderação inválida.");
      }
      const found = await this.repository.moderateTopic(moderatorId, id, action, reason);
      return { found, message: topicActionMessages[action] ?? "Ação concluída." };
    }
    if (input.tipo === "resposta") {
      if (!ForumReplyActionSchema.safeParse(action).success) {
        throw new ForumValidationError("Ação de moderação inválida.");
      }
      const found = await this.repository.moderateReply(moderatorId, id, action as string, reason);
      return { found, message: replyActionMessages[action as string] ?? "Ação concluída." };
    }
    throw new ForumValidationError("Tipo de conteúdo inválido.");
  }

  async changeUser(
    moderatorId: number,
    rawInput: unknown,
  ): Promise<{ found: boolean; message: string }> {
    if (typeof rawInput !== "object" || rawInput === null) {
      throw new ForumValidationError("Ação de usuário inválida.");
    }
    const input = rawInput as Record<string, unknown>;
    const userId = positiveQueryInteger(String(input.usuario_id ?? ""), "Usuário");
    const action = input.acao;
    if (!userId || !["promover", "rebaixar", "banir", "desbanir"].includes(String(action))) {
      throw new ForumValidationError("Ação de usuário inválida.");
    }
    const state = action as "promover" | "rebaixar" | "banir" | "desbanir";
    const found = await this.repository.changeUserState(
      moderatorId,
      userId,
      state,
      typeof input.motivo === "string" ? input.motivo : null,
    );
    return {
      found,
      message: userActionMessages[state] ?? "Ação concluída.",
    };
  }
}

export type ForumAdminPayload = {
  stats: Awaited<ReturnType<ForumRepository["getAdminStats"]>>;
  topics: ForumTopic[];
  pendingReplies: Array<ForumReply & { topico_titulo: string }>;
  categories: ForumCategory[];
  users: ForumAdminUser[];
  logs: ForumModerationLog[];
};
