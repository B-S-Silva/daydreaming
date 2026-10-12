import { and, eq, gt, lte, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import * as schema from "../db/schema.js";

export interface AuthUser {
  id: number;
  name: string;
  username: string;
  passwordHash: string;
  isAdmin: boolean;
  active: boolean;
}

export type AuthIdentity = Omit<AuthUser, "passwordHash">;

export interface AuthSession {
  userId: number | null;
  csrfToken: string;
  flashMessage: string | null;
}

export interface NewAccount {
  name: string;
  username: string;
  email: string;
  passwordHash: string;
}

export interface AuthRepository {
  findUserByUsername(username: string): Promise<AuthUser | null>;
  findUserById(userId: number): Promise<AuthIdentity | null>;
  findDuplicateField(username: string, email: string): Promise<"username" | "email" | null>;
  createUser(account: NewAccount): Promise<number>;
  recordLoginAttempt(userId: number, ipAddress: string, succeeded: boolean): Promise<void>;
  updateLastAccess(userId: number): Promise<void>;
  recordLogout(userId: number): Promise<void>;
  findSession(sessionIdHash: string): Promise<AuthSession | null>;
  createSession(
    sessionIdHash: string,
    userId: number | null,
    csrfToken: string,
    ttlSeconds: number,
  ): Promise<void>;
  setFlashMessage(sessionIdHash: string, message: string): Promise<void>;
  consumeFlashMessage(sessionIdHash: string): Promise<string | null>;
  deleteSession(sessionIdHash: string): Promise<void>;
  deleteExpiredSessions(): Promise<void>;
}

export class MySqlAuthRepository implements AuthRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async findUserByUsername(username: string): Promise<AuthUser | null> {
    const [user] = await this.db
      .select({
        id: schema.usuarios.id,
        name: schema.usuarios.nome,
        username: schema.usuarios.usuario,
        passwordHash: schema.usuarios.senha,
        isAdmin: schema.usuarios.isAdmin,
        active: schema.usuarios.ativo,
      })
      .from(schema.usuarios)
      .where(eq(schema.usuarios.usuario, username))
      .limit(1);

    return user
      ? {
          ...user,
          isAdmin: Boolean(user.isAdmin),
          active: Boolean(user.active),
        }
      : null;
  }

  async findUserById(userId: number): Promise<AuthIdentity | null> {
    const [user] = await this.db
      .select({
        id: schema.usuarios.id,
        name: schema.usuarios.nome,
        username: schema.usuarios.usuario,
        isAdmin: schema.usuarios.isAdmin,
        active: schema.usuarios.ativo,
      })
      .from(schema.usuarios)
      .where(eq(schema.usuarios.id, userId))
      .limit(1);

    return user
      ? {
          ...user,
          isAdmin: Boolean(user.isAdmin),
          active: Boolean(user.active),
        }
      : null;
  }

  async findDuplicateField(username: string, email: string): Promise<"username" | "email" | null> {
    const [usernameMatch] = await this.db
      .select({ id: schema.usuarios.id })
      .from(schema.usuarios)
      .where(eq(schema.usuarios.usuario, username))
      .limit(1);
    if (usernameMatch) {
      return "username";
    }

    const [emailMatch] = await this.db
      .select({ id: schema.usuarios.id })
      .from(schema.usuarios)
      .where(eq(schema.usuarios.email, email))
      .limit(1);
    return emailMatch ? "email" : null;
  }

  async createUser(account: NewAccount): Promise<number> {
    const [created] = await this.db
      .insert(schema.usuarios)
      .values({
        nome: account.name,
        usuario: account.username,
        email: account.email,
        senha: account.passwordHash,
      })
      .$returningId();
    if (!created) {
      throw new Error("MySQL did not return the inserted user ID.");
    }
    return created.id;
  }

  async recordLoginAttempt(userId: number, ipAddress: string, succeeded: boolean): Promise<void> {
    await this.db.insert(schema.logsAcesso).values({
      usuarioId: userId,
      ip: ipAddress,
      sucesso: succeeded,
    });
  }

  async updateLastAccess(userId: number): Promise<void> {
    await this.db
      .update(schema.usuarios)
      .set({ ultimoAcesso: sql`CURRENT_TIMESTAMP` })
      .where(eq(schema.usuarios.id, userId));
  }

  async recordLogout(userId: number): Promise<void> {
    await this.db
      .update(schema.usuarios)
      .set({ ultimoLogout: sql`CURRENT_TIMESTAMP` })
      .where(eq(schema.usuarios.id, userId));
    await this.db.insert(schema.logsSistema).values({
      usuarioId: userId,
      acao: "logout",
      detalhes: "Usuário fez logout",
      dataHora: sql`CURRENT_TIMESTAMP`,
    });
  }

  async findSession(sessionIdHash: string): Promise<AuthSession | null> {
    const [session] = await this.db
      .select({
        userId: schema.nodeSessions.usuarioId,
        csrfToken: schema.nodeSessions.csrfToken,
        flashMessage: schema.nodeSessions.mensagem,
      })
      .from(schema.nodeSessions)
      .where(
        and(
          eq(schema.nodeSessions.idHash, sessionIdHash),
          gt(schema.nodeSessions.expiraEm, sql`CURRENT_TIMESTAMP`),
        ),
      )
      .limit(1);

    return session ?? null;
  }

  async createSession(
    sessionIdHash: string,
    userId: number | null,
    csrfToken: string,
    ttlSeconds: number,
  ): Promise<void> {
    await this.db.insert(schema.nodeSessions).values({
      idHash: sessionIdHash,
      usuarioId: userId,
      csrfToken,
      expiraEm: sql`DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ${ttlSeconds} SECOND)`,
    });
  }

  async setFlashMessage(sessionIdHash: string, message: string): Promise<void> {
    await this.db
      .update(schema.nodeSessions)
      .set({ mensagem: message })
      .where(eq(schema.nodeSessions.idHash, sessionIdHash));
  }

  async consumeFlashMessage(sessionIdHash: string): Promise<string | null> {
    return this.db.transaction(async (transaction) => {
      const [session] = await transaction
        .select({ message: schema.nodeSessions.mensagem })
        .from(schema.nodeSessions)
        .where(
          and(
            eq(schema.nodeSessions.idHash, sessionIdHash),
            gt(schema.nodeSessions.expiraEm, sql`CURRENT_TIMESTAMP`),
          ),
        )
        .for("update");

      const message = session?.message ?? null;
      if (message !== null) {
        await transaction
          .update(schema.nodeSessions)
          .set({ mensagem: null })
          .where(eq(schema.nodeSessions.idHash, sessionIdHash));
      }
      return message;
    });
  }

  async deleteSession(sessionIdHash: string): Promise<void> {
    await this.db.delete(schema.nodeSessions).where(eq(schema.nodeSessions.idHash, sessionIdHash));
  }

  async deleteExpiredSessions(): Promise<void> {
    await this.db
      .delete(schema.nodeSessions)
      .where(lte(schema.nodeSessions.expiraEm, sql`CURRENT_TIMESTAMP`));
  }
}
