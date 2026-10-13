import { and, count, desc, eq, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type {
  ForumAdminUser,
  ForumCategory,
  ForumModerationLog,
  ForumReply,
  ForumTopic,
} from "@daydreaming/shared";
import type { ForumTopicFilter } from "./forum-service.js";
import * as schema from "../db/schema.js";

export interface ForumRepository {
  listCategories(activeOnly: boolean): Promise<ForumCategory[]>;
  listTopics(filter: ForumTopicFilter): Promise<ForumTopic[]>;
  findTopic(id: number): Promise<ForumTopic | null>;
  incrementTopicViews(id: number): Promise<void>;
  listReplies(topicId: number): Promise<ForumReply[]>;
  createTopic(categoryId: number, userId: number, title: string, content: string): Promise<void>;
  createReply(topicId: number, userId: number, content: string): Promise<void>;
  notifyReply(topicId: number, userId: number, content: string): Promise<void>;
  createLike(userId: number, type: "topico" | "resposta", id: number): Promise<void>;
  moderateTopic(
    moderatorId: number,
    topicId: number,
    action: string,
    reason?: string | null,
  ): Promise<boolean>;
  moderateReply(
    moderatorId: number,
    replyId: number,
    action: string,
    reason?: string | null,
  ): Promise<boolean>;
  getAdminStats(): Promise<{
    total_topicos: number;
    topicos_pendentes: number;
    total_respostas: number;
    respostas_pendentes: number;
    total_usuarios: number;
  }>;
  listPendingReplies(): Promise<Array<ForumReply & { topico_titulo: string }>>;
  listAdminUsers(): Promise<ForumAdminUser[]>;
  listModerationLogs(): Promise<ForumModerationLog[]>;
  createCategory(category: {
    nome: string;
    descricao: string;
    cor: string;
    icone: string;
  }): Promise<void>;
  changeUserState(
    moderatorId: number,
    targetUserId: number,
    state: "promover" | "rebaixar" | "banir" | "desbanir",
    reason?: string | null,
  ): Promise<boolean>;
}

function dateString(value: Date | null): string {
  return value ? value.toISOString() : "";
}

function toForumCategory(row: typeof schema.forumCategorias.$inferSelect): ForumCategory {
  return {
    id: row.id,
    nome: row.nome,
    descricao: row.descricao,
    cor: row.cor,
    icone: row.icone,
    ativo: Boolean(row.ativo),
  };
}

export class MySqlForumRepository implements ForumRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async listCategories(activeOnly: boolean): Promise<ForumCategory[]> {
    const query = this.db.select().from(schema.forumCategorias);
    const rows = activeOnly
      ? await query
          .where(eq(schema.forumCategorias.ativo, true))
          .orderBy(schema.forumCategorias.nome)
      : await query.orderBy(schema.forumCategorias.nome);
    return rows.map(toForumCategory);
  }

  async listTopics(filter: ForumTopicFilter): Promise<ForumTopic[]> {
    const conditions = [];
    if (filter.categoryId !== undefined) {
      conditions.push(eq(schema.forumTopicos.categoriaId, filter.categoryId));
    }
    if (filter.search) {
      const match = `%${filter.search}%`;
      conditions.push(
        sql`(${schema.forumTopicos.titulo} LIKE ${match} OR ${schema.forumTopicos.conteudo} LIKE ${match})`,
      );
    }
    if (filter.moderationFilter === "pendentes") {
      conditions.push(eq(schema.forumTopicos.aprovado, false));
    } else if (filter.moderationFilter === "fixados") {
      conditions.push(eq(schema.forumTopicos.fixado, true));
    } else if (filter.moderationFilter === "fechados") {
      conditions.push(eq(schema.forumTopicos.fechado, true));
    }

    const query = this.db
      .select({
        id: schema.forumTopicos.id,
        categoria_id: schema.forumTopicos.categoriaId,
        titulo: schema.forumTopicos.titulo,
        conteudo: schema.forumTopicos.conteudo,
        visualizacoes: schema.forumTopicos.visualizacoes,
        fixado: schema.forumTopicos.fixado,
        fechado: schema.forumTopicos.fechado,
        aprovado: schema.forumTopicos.aprovado,
        data_criacao: schema.forumTopicos.dataCriacao,
        data_atualizacao: schema.forumTopicos.dataAtualizacao,
        autor_id: schema.forumTopicos.autorId,
        autor_nome: schema.usuarios.nome,
        categoria_nome: schema.forumCategorias.nome,
        categoria_cor: schema.forumCategorias.cor,
        categoria_icone: schema.forumCategorias.icone,
        total_respostas: sql<number>`(SELECT COUNT(*) FROM ${schema.forumRespostas} WHERE ${schema.forumRespostas.topicoId} = ${schema.forumTopicos.id})`,
        total_likes: sql<number>`(SELECT COUNT(*) FROM ${schema.forumCurtidas} WHERE ${schema.forumCurtidas.topicoId} = ${schema.forumTopicos.id})`,
      })
      .from(schema.forumTopicos)
      .innerJoin(schema.usuarios, eq(schema.forumTopicos.autorId, schema.usuarios.id))
      .innerJoin(
        schema.forumCategorias,
        eq(schema.forumTopicos.categoriaId, schema.forumCategorias.id),
      )
      .orderBy(
        ...(filter.admin
          ? [desc(schema.forumTopicos.dataCriacao)]
          : [desc(schema.forumTopicos.fixado), desc(schema.forumTopicos.dataAtualizacao)]),
      )
      .limit(filter.limit);
    const rows = conditions.length ? await query.where(and(...conditions)) : await query;
    return rows.map((row) => ({
      ...row,
      visualizacoes: Number(row.visualizacoes),
      fixado: Boolean(row.fixado),
      fechado: Boolean(row.fechado),
      aprovado: Boolean(row.aprovado),
      data_criacao: dateString(row.data_criacao),
      data_atualizacao: dateString(row.data_atualizacao),
      total_respostas: Number(row.total_respostas),
      total_likes: Number(row.total_likes),
    }));
  }

  async findTopic(id: number): Promise<ForumTopic | null> {
    const [row] = await this.db
      .select({
        id: schema.forumTopicos.id,
        categoria_id: schema.forumTopicos.categoriaId,
        titulo: schema.forumTopicos.titulo,
        conteudo: schema.forumTopicos.conteudo,
        visualizacoes: schema.forumTopicos.visualizacoes,
        fixado: schema.forumTopicos.fixado,
        fechado: schema.forumTopicos.fechado,
        aprovado: schema.forumTopicos.aprovado,
        data_criacao: schema.forumTopicos.dataCriacao,
        data_atualizacao: schema.forumTopicos.dataAtualizacao,
        autor_id: schema.forumTopicos.autorId,
        autor_nome: schema.usuarios.nome,
        categoria_nome: schema.forumCategorias.nome,
        categoria_cor: schema.forumCategorias.cor,
        categoria_icone: schema.forumCategorias.icone,
        total_respostas: sql<number>`(SELECT COUNT(*) FROM ${schema.forumRespostas} WHERE ${schema.forumRespostas.topicoId} = ${schema.forumTopicos.id})`,
        total_likes: sql<number>`(SELECT COUNT(*) FROM ${schema.forumCurtidas} WHERE ${schema.forumCurtidas.topicoId} = ${schema.forumTopicos.id})`,
      })
      .from(schema.forumTopicos)
      .innerJoin(schema.usuarios, eq(schema.forumTopicos.autorId, schema.usuarios.id))
      .innerJoin(
        schema.forumCategorias,
        eq(schema.forumTopicos.categoriaId, schema.forumCategorias.id),
      )
      .where(and(eq(schema.forumTopicos.id, id), eq(schema.forumTopicos.aprovado, true)))
      .limit(1);

    return row
      ? {
          ...row,
          visualizacoes: Number(row.visualizacoes),
          fixado: Boolean(row.fixado),
          fechado: Boolean(row.fechado),
          aprovado: Boolean(row.aprovado),
          data_criacao: dateString(row.data_criacao),
          data_atualizacao: dateString(row.data_atualizacao),
          total_respostas: Number(row.total_respostas),
          total_likes: Number(row.total_likes),
        }
      : null;
  }

  async incrementTopicViews(id: number): Promise<void> {
    await this.db
      .update(schema.forumTopicos)
      .set({ visualizacoes: sql`${schema.forumTopicos.visualizacoes} + 1` })
      .where(eq(schema.forumTopicos.id, id));
  }

  async listReplies(topicId: number): Promise<ForumReply[]> {
    const rows = await this.db
      .select({
        id: schema.forumRespostas.id,
        topico_id: schema.forumRespostas.topicoId,
        autor_id: schema.forumRespostas.autorId,
        autor_nome: schema.usuarios.nome,
        conteudo: schema.forumRespostas.conteudo,
        aprovado: schema.forumRespostas.aprovado,
        data_criacao: schema.forumRespostas.dataCriacao,
        total_likes: sql<number>`(SELECT COUNT(*) FROM ${schema.forumCurtidas} WHERE ${schema.forumCurtidas.respostaId} = ${schema.forumRespostas.id})`,
      })
      .from(schema.forumRespostas)
      .innerJoin(schema.usuarios, eq(schema.forumRespostas.autorId, schema.usuarios.id))
      .where(eq(schema.forumRespostas.topicoId, topicId))
      .orderBy(schema.forumRespostas.dataCriacao);
    return rows.map((row) => ({
      ...row,
      aprovado: Boolean(row.aprovado),
      data_criacao: dateString(row.data_criacao),
      total_likes: Number(row.total_likes),
    }));
  }

  async createTopic(
    categoryId: number,
    userId: number,
    title: string,
    content: string,
  ): Promise<void> {
    await this.db.insert(schema.forumTopicos).values({
      categoriaId: categoryId,
      autorId: userId,
      usuarioId: userId,
      titulo: title,
      conteudo: content,
      aprovado: true,
    });
  }

  async createReply(topicId: number, userId: number, content: string): Promise<void> {
    await this.db.insert(schema.forumRespostas).values({
      topicoId: topicId,
      autorId: userId,
      usuarioId: userId,
      conteudo: content,
      aprovado: true,
    });
  }

  async notifyReply(topicId: number, userId: number, content: string): Promise<void> {
    await this.db.transaction(async (transaction) => {
      const [topic] = await transaction
        .select({
          ownerId: schema.forumTopicos.usuarioId,
          title: schema.forumTopicos.titulo,
        })
        .from(schema.forumTopicos)
        .where(eq(schema.forumTopicos.id, topicId))
        .limit(1);
      const [author] = await transaction
        .select({ name: schema.usuarios.nome })
        .from(schema.usuarios)
        .where(eq(schema.usuarios.id, userId))
        .limit(1);
      if (!topic || !author) {
        return;
      }

      const link = `/forum/topics/${topicId}`;
      if (topic.ownerId !== null && topic.ownerId !== userId) {
        await transaction.insert(schema.notificacoesUsuario).values({
          usuarioId: topic.ownerId,
          tipo: "forum_resposta",
          titulo: "Nova resposta no seu tópico",
          mensagem: `${author.name} respondeu ao seu tópico "${topic.title}"`,
          link,
        });
      }

      const usernames = [...content.matchAll(/@([a-zA-Z0-9_]+)/g)].map((match) => match[1]);
      for (const username of usernames) {
        if (!username) {
          continue;
        }
        const [mentionedUser] = await transaction
          .select({ id: schema.usuarios.id })
          .from(schema.usuarios)
          .where(eq(schema.usuarios.usuario, username))
          .limit(1);
        if (mentionedUser && mentionedUser.id !== userId) {
          await transaction.insert(schema.notificacoesUsuario).values({
            usuarioId: mentionedUser.id,
            tipo: "forum_mencao",
            titulo: "Você foi mencionado no fórum",
            mensagem: `${author.name} mencionou você no tópico "${topic.title}"`,
            link,
          });
        }
      }
    });
  }

  async createLike(userId: number, type: "topico" | "resposta", id: number): Promise<void> {
    await this.db
      .insert(schema.forumCurtidas)
      .ignore()
      .values({
        usuarioId: userId,
        topicoId: type === "topico" ? id : null,
        respostaId: type === "resposta" ? id : null,
      });
  }

  async moderateTopic(
    moderatorId: number,
    topicId: number,
    action: string,
    reason?: string | null,
  ): Promise<boolean> {
    return this.db.transaction(async (transaction) => {
      const [topic] = await transaction
        .select({
          id: schema.forumTopicos.id,
          pinned: schema.forumTopicos.fixado,
          closed: schema.forumTopicos.fechado,
        })
        .from(schema.forumTopicos)
        .where(eq(schema.forumTopicos.id, topicId))
        .limit(1);
      if (!topic) {
        return false;
      }
      if (action === "deletar") {
        await transaction.delete(schema.forumTopicos).where(eq(schema.forumTopicos.id, topicId));
      } else {
        const update =
          action === "aprovar"
            ? { aprovado: true }
            : action === "rejeitar"
              ? { aprovado: false }
              : action === "fixar" || action === "desfixar"
                ? { fixado: action === "fixar" }
                : { fechado: action === "fechar" };
        await transaction
          .update(schema.forumTopicos)
          .set(update)
          .where(eq(schema.forumTopicos.id, topicId));
      }
      await transaction.insert(schema.forumModeracao).values({
        moderadorId: moderatorId,
        tipoConteudo: "topico",
        conteudoId: topicId,
        topicoId: topicId,
        acao: action,
        motivo: reason ?? null,
      });
      return true;
    });
  }

  async moderateReply(
    moderatorId: number,
    replyId: number,
    action: string,
    reason?: string | null,
  ): Promise<boolean> {
    return this.db.transaction(async (transaction) => {
      const [reply] = await transaction
        .select({ id: schema.forumRespostas.id })
        .from(schema.forumRespostas)
        .where(eq(schema.forumRespostas.id, replyId))
        .limit(1);
      if (!reply) {
        return false;
      }
      if (action === "deletar") {
        await transaction
          .delete(schema.forumRespostas)
          .where(eq(schema.forumRespostas.id, replyId));
      } else {
        await transaction
          .update(schema.forumRespostas)
          .set({ aprovado: action === "aprovar" })
          .where(eq(schema.forumRespostas.id, replyId));
      }
      await transaction.insert(schema.forumModeracao).values({
        moderadorId: moderatorId,
        tipoConteudo: "resposta",
        conteudoId: replyId,
        respostaId: replyId,
        acao: action,
        motivo: reason ?? null,
      });
      return true;
    });
  }

  async getAdminStats() {
    const [topics] = await this.db.select({ total: count() }).from(schema.forumTopicos);
    const [pendingTopics] = await this.db
      .select({ total: count() })
      .from(schema.forumTopicos)
      .where(eq(schema.forumTopicos.aprovado, false));
    const [replies] = await this.db.select({ total: count() }).from(schema.forumRespostas);
    const [pendingReplies] = await this.db
      .select({ total: count() })
      .from(schema.forumRespostas)
      .where(eq(schema.forumRespostas.aprovado, false));
    const [users] = await this.db.select({ total: count() }).from(schema.usuarios);
    return {
      total_topicos: Number(topics?.total ?? 0),
      topicos_pendentes: Number(pendingTopics?.total ?? 0),
      total_respostas: Number(replies?.total ?? 0),
      respostas_pendentes: Number(pendingReplies?.total ?? 0),
      total_usuarios: Number(users?.total ?? 0),
    };
  }

  async listPendingReplies(): Promise<Array<ForumReply & { topico_titulo: string }>> {
    const rows = await this.db
      .select({
        id: schema.forumRespostas.id,
        topico_id: schema.forumRespostas.topicoId,
        autor_id: schema.forumRespostas.autorId,
        autor_nome: schema.usuarios.nome,
        conteudo: schema.forumRespostas.conteudo,
        aprovado: schema.forumRespostas.aprovado,
        data_criacao: schema.forumRespostas.dataCriacao,
        total_likes: sql<number>`(SELECT COUNT(*) FROM ${schema.forumCurtidas} WHERE ${schema.forumCurtidas.respostaId} = ${schema.forumRespostas.id})`,
        topico_titulo: schema.forumTopicos.titulo,
      })
      .from(schema.forumRespostas)
      .innerJoin(schema.usuarios, eq(schema.forumRespostas.autorId, schema.usuarios.id))
      .innerJoin(schema.forumTopicos, eq(schema.forumRespostas.topicoId, schema.forumTopicos.id))
      .where(eq(schema.forumRespostas.aprovado, false))
      .orderBy(desc(schema.forumRespostas.dataCriacao))
      .limit(20);
    return rows.map((row) => ({
      ...row,
      aprovado: Boolean(row.aprovado),
      data_criacao: dateString(row.data_criacao),
      total_likes: Number(row.total_likes),
    }));
  }

  async listAdminUsers(): Promise<ForumAdminUser[]> {
    const rows = await this.db
      .select({
        id: schema.usuarios.id,
        nome: schema.usuarios.nome,
        usuario: schema.usuarios.usuario,
        email: schema.usuarios.email,
        is_admin: schema.usuarios.isAdmin,
        ativo: schema.usuarios.ativo,
        data_criacao: schema.usuarios.dataCriacao,
        total_topicos: sql<number>`(SELECT COUNT(*) FROM ${schema.forumTopicos} WHERE ${schema.forumTopicos.autorId} = ${schema.usuarios.id})`,
        total_respostas: sql<number>`(SELECT COUNT(*) FROM ${schema.forumRespostas} WHERE ${schema.forumRespostas.autorId} = ${schema.usuarios.id})`,
      })
      .from(schema.usuarios)
      .orderBy(desc(schema.usuarios.dataCriacao));
    return rows.map((row) => ({
      ...row,
      is_admin: Boolean(row.is_admin),
      ativo: Boolean(row.ativo),
      data_criacao: dateString(row.data_criacao),
      total_topicos: Number(row.total_topicos),
      total_respostas: Number(row.total_respostas),
    }));
  }

  async listModerationLogs(): Promise<ForumModerationLog[]> {
    const rows = await this.db
      .select({
        id: schema.forumModeracao.id,
        moderador_nome: schema.usuarios.nome,
        tipo_conteudo: schema.forumModeracao.tipoConteudo,
        conteudo_id: schema.forumModeracao.conteudoId,
        acao: schema.forumModeracao.acao,
        motivo: schema.forumModeracao.motivo,
        data_acao: schema.forumModeracao.dataAcao,
      })
      .from(schema.forumModeracao)
      .innerJoin(schema.usuarios, eq(schema.forumModeracao.moderadorId, schema.usuarios.id))
      .orderBy(desc(schema.forumModeracao.dataAcao))
      .limit(20);
    return rows.map((row) => ({ ...row, data_acao: dateString(row.data_acao) }));
  }

  async createCategory(category: {
    nome: string;
    descricao: string;
    cor: string;
    icone: string;
  }): Promise<void> {
    await this.db.insert(schema.forumCategorias).values({
      ...category,
      ordem: 0,
    });
  }

  async changeUserState(
    moderatorId: number,
    targetUserId: number,
    state: "promover" | "rebaixar" | "banir" | "desbanir",
    reason?: string | null,
  ): Promise<boolean> {
    return this.db.transaction(async (transaction) => {
      const [user] = await transaction
        .select({ id: schema.usuarios.id })
        .from(schema.usuarios)
        .where(eq(schema.usuarios.id, targetUserId))
        .limit(1);
      if (!user || targetUserId === moderatorId) {
        return false;
      }
      await transaction
        .update(schema.usuarios)
        .set({
          isAdmin: state === "promover" ? true : state === "rebaixar" ? false : undefined,
          ativo: state === "banir" ? false : state === "desbanir" ? true : undefined,
        })
        .where(eq(schema.usuarios.id, targetUserId));
      await transaction.insert(schema.forumModeracao).values({
        moderadorId: moderatorId,
        tipoConteudo: "usuario",
        conteudoId: targetUserId,
        acao: state,
        motivo: reason ?? null,
      });
      return true;
    });
  }
}
