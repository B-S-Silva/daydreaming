// @vitest-environment node
import bcrypt from "bcryptjs";
import type {
  AuthIdentity,
  AuthRepository,
  AuthSession,
  AuthUser,
  NewAccount,
} from "../src/auth/auth-repository.js";
import { createApp } from "../src/app.js";
import { authCookieName } from "../src/auth/auth-routes.js";
import { afterEach, beforeEach, describe, expect, it } from "vitest";

class FakeAuthRepository implements AuthRepository {
  readonly users: AuthUser[];
  readonly sessions = new Map<string, AuthSession>();
  readonly loginAttempts: Array<{ userId: number; ipAddress: string; succeeded: boolean }> = [];
  readonly lastAccessedUserIds: number[] = [];
  readonly logoutEvents: number[] = [];
  readonly sessionTtls: number[] = [];
  private readonly emails = new Set(["aluno@example.com"]);
  private nextUserId = 2;

  constructor(user: AuthUser) {
    this.users = [user];
  }

  async findUserByUsername(username: string): Promise<AuthUser | null> {
    return this.users.find((user) => user.username === username) ?? null;
  }

  async findUserById(userId: number): Promise<AuthIdentity | null> {
    const user = this.users.find((entry) => entry.id === userId);
    if (!user) {
      return null;
    }
    return {
      id: user.id,
      name: user.name,
      username: user.username,
      isAdmin: user.isAdmin,
      active: user.active,
    };
  }

  async findDuplicateField(username: string, email: string): Promise<"username" | "email" | null> {
    if (this.users.some((user) => user.username === username)) {
      return "username";
    }
    if (this.emails.has(email)) {
      return "email";
    }
    return null;
  }

  async createUser(account: NewAccount): Promise<number> {
    const id = this.nextUserId++;
    this.users.push({
      id,
      name: account.name,
      username: account.username,
      passwordHash: account.passwordHash,
      isAdmin: false,
      active: true,
    });
    this.emails.add(account.email);
    return id;
  }

  async recordLoginAttempt(userId: number, ipAddress: string, succeeded: boolean): Promise<void> {
    this.loginAttempts.push({ userId, ipAddress, succeeded });
  }

  async updateLastAccess(userId: number): Promise<void> {
    this.lastAccessedUserIds.push(userId);
  }

  async recordLogout(userId: number): Promise<void> {
    this.logoutEvents.push(userId);
  }

  async findSession(sessionIdHash: string): Promise<AuthSession | null> {
    return this.sessions.get(sessionIdHash) ?? null;
  }

  async createSession(
    sessionIdHash: string,
    userId: number | null,
    csrfToken: string,
    ttlSeconds: number,
  ): Promise<void> {
    this.sessionTtls.push(ttlSeconds);
    this.sessions.set(sessionIdHash, { userId, csrfToken, flashMessage: null });
  }

  async setFlashMessage(sessionIdHash: string, message: string): Promise<void> {
    const session = this.sessions.get(sessionIdHash);
    if (session) {
      session.flashMessage = message;
    }
  }

  async consumeFlashMessage(sessionIdHash: string): Promise<string | null> {
    const session = this.sessions.get(sessionIdHash);
    if (!session) {
      return null;
    }
    const message = session.flashMessage;
    session.flashMessage = null;
    return message;
  }

  async deleteSession(sessionIdHash: string): Promise<void> {
    this.sessions.delete(sessionIdHash);
  }

  async deleteExpiredSessions(): Promise<void> {}
}

describe("authentication API", () => {
  let repository: FakeAuthRepository;
  let app = createApp();

  beforeEach(async () => {
    repository = new FakeAuthRepository({
      id: 1,
      name: "Test User",
      username: "aluno",
      passwordHash: await bcrypt.hash("senha123", 4),
      isAdmin: false,
      active: true,
    });
    app = createApp({
      auth: {
        repository,
        sessionTtlSeconds: 3600,
        cookieSecure: false,
        webOrigin: "http://localhost:5173",
      },
    });
  });

  afterEach(async () => {
    await app.close();
  });

  async function createSession(): Promise<{ cookieHeader: string; csrfToken: string }> {
    const response = await app.inject({ method: "GET", url: "/api/auth/me" });
    const cookieHeader = response.headers["set-cookie"];
    if (!cookieHeader) {
      throw new Error("Expected current-user endpoint to create a session cookie.");
    }
    return {
      cookieHeader: cookieHeader.split(";", 1)[0]!,
      csrfToken: response.json().csrfToken as string,
    };
  }

  it("creates an anonymous session and requires its CSRF token for mutations", async () => {
    const session = await createSession();
    expect(session.cookieHeader).toMatch(new RegExp(`^${authCookieName}=`));

    const response = await app.inject({
      method: "POST",
      url: "/api/auth/login",
      headers: { cookie: session.cookieHeader },
      payload: { username: "aluno", password: "senha123" },
    });

    expect(response.statusCode).toBe(403);
    expect(response.json()).toEqual({ message: "Token de segurança inválido." });
  });

  it("logs in, rotates the session cookie, and returns the authenticated user", async () => {
    const session = await createSession();
    const response = await app.inject({
      method: "POST",
      url: "/api/auth/login",
      headers: {
        cookie: session.cookieHeader,
        "x-csrf-token": session.csrfToken,
      },
      payload: { username: " aluno ", password: " senha123 " },
    });

    expect(response.statusCode).toBe(200);
    expect(response.json()).toMatchObject({
      success: true,
      user: { id: 1, username: "aluno", name: "Test User", isAdmin: false },
    });
    const rotatedCookie = response.headers["set-cookie"];
    expect(rotatedCookie).toBeTruthy();
    expect(rotatedCookie).not.toContain(session.cookieHeader.split("=")[1]!.split(";")[0]!);

    const current = await app.inject({
      method: "GET",
      url: "/api/auth/me",
      headers: { cookie: rotatedCookie!.split(";", 1)[0]! },
    });
    expect(current.json()).toMatchObject({
      authenticated: true,
      user: { id: 1, username: "aluno" },
    });
    expect(current.json().user.passwordHash).toBeUndefined();
    expect(repository.loginAttempts).toEqual([
      { userId: 1, ipAddress: "127.0.0.1", succeeded: true },
    ]);
    expect(repository.lastAccessedUserIds).toEqual([1]);
  });

  it("limits repeated login requests by client IP", async () => {
    const session = await createSession();
    const responses = [];
    for (let attempt = 0; attempt < 6; attempt += 1) {
      responses.push(
        await app.inject({
          method: "POST",
          url: "/api/auth/login",
          headers: {
            cookie: session.cookieHeader,
            "x-csrf-token": session.csrfToken,
          },
          payload: { username: "aluno", password: "incorrecta" },
        }),
      );
    }

    expect(responses.slice(0, 5).every((response) => response.statusCode === 401)).toBe(true);
    expect(responses[5]?.statusCode).toBe(429);
    expect(responses[5]?.json()).toEqual({
      message: "Muitas tentativas. Aguarde antes de tentar novamente.",
    });
  });

  it("keeps PHP-compatible login failure distinctions", async () => {
    const session = await createSession();
    const bannedUser = repository.users[0]!;
    bannedUser.active = false;

    const response = await app.inject({
      method: "POST",
      url: "/api/auth/login",
      headers: {
        cookie: session.cookieHeader,
        "x-csrf-token": session.csrfToken,
      },
      payload: { username: "aluno", password: "senha123" },
    });

    expect(response.statusCode).toBe(403);
    expect(response.json()).toEqual({
      message: "Este usuário foi banido por violar as regras de conduta do Portal!",
    });
    expect(repository.loginAttempts).toEqual([
      { userId: 1, ipAddress: "127.0.0.1", succeeded: false },
    ]);
  });

  it("accepts the PHP bcrypt $2y$ prefix without rewriting stored hashes", async () => {
    const session = await createSession();
    const user = repository.users[0]!;
    const originalHash = user.passwordHash;
    user.passwordHash = originalHash.replace(/^\$2b\$/, "$2y$");

    const response = await app.inject({
      method: "POST",
      url: "/api/auth/login",
      headers: {
        cookie: session.cookieHeader,
        "x-csrf-token": session.csrfToken,
      },
      payload: { username: "aluno", password: "senha123" },
    });

    expect(response.statusCode).toBe(200);
    expect(user.passwordHash).toMatch(/^\$2y\$/);
  });

  it("registers and flashes the PHP success message once", async () => {
    const session = await createSession();
    const response = await app.inject({
      method: "POST",
      url: "/api/auth/register",
      headers: {
        cookie: session.cookieHeader,
        "x-csrf-token": session.csrfToken,
      },
      payload: {
        name: "Nova Pessoa",
        username: "novapessoa",
        email: "nova@example.com",
        password: "senha123",
        confirmPassword: "senha123",
      },
    });

    expect(response.statusCode).toBe(201);
    expect(repository.users).toHaveLength(2);
    const current = await app.inject({
      method: "GET",
      url: "/api/auth/me",
      headers: { cookie: session.cookieHeader },
    });
    expect(current.json().flashMessage).toBe(
      "Cadastro realizado com sucesso! Faça login para continuar.",
    );
    const nextCurrent = await app.inject({
      method: "GET",
      url: "/api/auth/me",
      headers: { cookie: session.cookieHeader },
    });
    expect(nextCurrent.json().flashMessage).toBeNull();
  });

  it("records logout and revokes the authenticated session", async () => {
    const session = await createSession();
    const loginResponse = await app.inject({
      method: "POST",
      url: "/api/auth/login",
      headers: {
        cookie: session.cookieHeader,
        "x-csrf-token": session.csrfToken,
      },
      payload: { username: "aluno", password: "senha123" },
    });
    const authenticatedCookie = loginResponse.headers["set-cookie"]!.split(";", 1)[0]!;
    const csrfToken = await app
      .inject({
        method: "GET",
        url: "/api/auth/me",
        headers: { cookie: authenticatedCookie },
      })
      .then((response) => response.json().csrfToken as string);

    const logoutResponse = await app.inject({
      method: "POST",
      url: "/api/auth/logout",
      headers: { cookie: authenticatedCookie, "x-csrf-token": csrfToken },
    });
    expect(logoutResponse.statusCode).toBe(200);
    expect(repository.logoutEvents).toEqual([1]);
    const anonymousCookie = logoutResponse.headers["set-cookie"]!.split(";", 1)[0]!;
    const current = await app.inject({
      method: "GET",
      url: "/api/auth/me",
      headers: { cookie: anonymousCookie },
    });
    expect(current.json()).toMatchObject({ authenticated: false });
    expect(current.json().flashMessage).toBe("Logout realizado com sucesso!");
  });

  it("does not claim that unsupported password recovery sent an email", async () => {
    const response = await app.inject({
      method: "POST",
      url: "/api/auth/password-recovery",
      payload: { email: "aluno@example.com" },
    });

    expect(response.statusCode).toBe(501);
    expect(response.json().message).toContain("não envia e-mail nem persiste tokens");
  });
});
