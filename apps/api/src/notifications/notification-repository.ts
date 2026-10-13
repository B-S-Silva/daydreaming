import { and, count, desc, eq } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type { Notification } from "@daydreaming/shared";
import * as schema from "../db/schema.js";

export interface NotificationRepository {
  list(userId: number, limit: number, unreadOnly: boolean): Promise<Notification[]>;
  unreadCount(userId: number): Promise<number>;
  markRead(userId: number, notificationId: number): Promise<boolean>;
  markAllRead(userId: number): Promise<void>;
}

function toNotification(row: typeof schema.notificacoesUsuario.$inferSelect): Notification {
  const typeByDatabaseValue = {
    forum_resposta: "forum_reply",
    forum_mencao: "forum_mention",
    badge_conquistada: "badge",
    nivel_subiu: "level",
    sistema: "system",
  } as const;

  return {
    id: row.id,
    type: typeByDatabaseValue[row.tipo],
    title: row.titulo,
    message: row.mensagem,
    link: row.link,
    read: row.lida,
    createdAt: row.dataCriacao.toISOString(),
  };
}

export class MySqlNotificationRepository implements NotificationRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async list(userId: number, limit: number, unreadOnly: boolean): Promise<Notification[]> {
    const conditions = [eq(schema.notificacoesUsuario.usuarioId, userId)];
    if (unreadOnly) {
      conditions.push(eq(schema.notificacoesUsuario.lida, false));
    }
    const rows = await this.db
      .select()
      .from(schema.notificacoesUsuario)
      .where(and(...conditions))
      .orderBy(desc(schema.notificacoesUsuario.dataCriacao))
      .limit(limit);
    return rows.map(toNotification);
  }

  async unreadCount(userId: number): Promise<number> {
    const [row] = await this.db
      .select({ total: count() })
      .from(schema.notificacoesUsuario)
      .where(
        and(
          eq(schema.notificacoesUsuario.usuarioId, userId),
          eq(schema.notificacoesUsuario.lida, false),
        ),
      );
    return Number(row?.total ?? 0);
  }

  async markRead(userId: number, notificationId: number): Promise<boolean> {
    const [notification] = await this.db
      .select({ id: schema.notificacoesUsuario.id })
      .from(schema.notificacoesUsuario)
      .where(
        and(
          eq(schema.notificacoesUsuario.id, notificationId),
          eq(schema.notificacoesUsuario.usuarioId, userId),
        ),
      )
      .limit(1);
    if (!notification) {
      return false;
    }
    await this.db
      .update(schema.notificacoesUsuario)
      .set({ lida: true })
      .where(
        and(
          eq(schema.notificacoesUsuario.id, notificationId),
          eq(schema.notificacoesUsuario.usuarioId, userId),
        ),
      );
    return true;
  }

  async markAllRead(userId: number): Promise<void> {
    await this.db
      .update(schema.notificacoesUsuario)
      .set({ lida: true })
      .where(
        and(
          eq(schema.notificacoesUsuario.usuarioId, userId),
          eq(schema.notificacoesUsuario.lida, false),
        ),
      );
  }
}
