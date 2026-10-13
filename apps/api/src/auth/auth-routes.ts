import { createHash, randomBytes, timingSafeEqual } from "node:crypto";
import cookie from "@fastify/cookie";
import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import {
  AuthErrorResponseSchema,
  AuthSuccessResponseSchema,
  AuthValidationResponseSchema,
  CurrentUserResponseSchema,
  LoginInputSchema,
  PasswordRecoveryInputSchema,
  RegistrationInputSchema,
} from "@daydreaming/shared";
import type { AuthRepository, AuthSession } from "./auth-repository.js";
import { AuthService } from "./auth-service.js";

const SESSION_COOKIE = "daydreaming_node_sid";
const SESSION_ID_PATTERN = /^[a-f0-9]{64}$/;

export interface AuthRouteOptions {
  repository: AuthRepository;
  sessionTtlSeconds: number;
  cookieSecure: boolean;
  webOrigin: string;
}

export interface ResolvedSession {
  idHash: string;
  session: AuthSession;
  user: Awaited<ReturnType<AuthRepository["findUserById"]>>;
}

function sessionHash(token: string): string {
  return createHash("sha256").update(token).digest("hex");
}

function csrfMatches(expected: string, actual: string | undefined): boolean {
  if (!/^[a-f0-9]{64}$/.test(expected) || !actual || !/^[a-f0-9]{64}$/.test(actual)) {
    return false;
  }
  return timingSafeEqual(Buffer.from(expected, "hex"), Buffer.from(actual, "hex"));
}

function setSessionCookie(reply: FastifyReply, token: string, options: AuthRouteOptions): void {
  reply.setCookie(SESSION_COOKIE, token, {
    path: "/",
    httpOnly: true,
    secure: options.cookieSecure,
    sameSite: "strict",
    maxAge: options.sessionTtlSeconds,
  });
}

function clearSessionCookie(reply: FastifyReply, options: AuthRouteOptions): void {
  reply.clearCookie(SESSION_COOKIE, {
    path: "/",
    httpOnly: true,
    secure: options.cookieSecure,
    sameSite: "strict",
  });
}

async function issueSession(
  reply: FastifyReply,
  options: AuthRouteOptions,
  userId: number | null,
): Promise<{ idHash: string; csrfToken: string }> {
  const token = randomBytes(32).toString("hex");
  const idHash = sessionHash(token);
  const csrfToken = randomBytes(32).toString("hex");
  await options.repository.createSession(idHash, userId, csrfToken, options.sessionTtlSeconds);
  setSessionCookie(reply, token, options);
  return { idHash, csrfToken };
}

export async function resolveSession(
  request: FastifyRequest,
  reply: FastifyReply,
  options: AuthRouteOptions,
): Promise<ResolvedSession> {
  const token = request.cookies[SESSION_COOKIE];
  if (token && SESSION_ID_PATTERN.test(token)) {
    const idHash = sessionHash(token);
    const session = await options.repository.findSession(idHash);
    if (session) {
      const user =
        session.userId === null ? null : await options.repository.findUserById(session.userId);
      if (session.userId === null || (user && user.active)) {
        return { idHash, session, user };
      }
      await options.repository.deleteSession(idHash);
    }
  }

  if (token) {
    clearSessionCookie(reply, options);
  }

  await options.repository.deleteExpiredSessions();
  const created = await issueSession(reply, options, null);
  return {
    idHash: created.idHash,
    session: { userId: null, csrfToken: created.csrfToken, flashMessage: null },
    user: null,
  };
}

export async function requireMutationSession(
  request: FastifyRequest,
  reply: FastifyReply,
  options: AuthRouteOptions,
): Promise<ResolvedSession | null> {
  const origin = request.headers.origin;
  if (origin && origin !== options.webOrigin) {
    reply.code(403).send(AuthErrorResponseSchema.parse({ message: "Origem não permitida." }));
    return null;
  }

  const token = request.cookies[SESSION_COOKIE];
  if (!token || !SESSION_ID_PATTERN.test(token)) {
    reply.code(403).send(
      AuthErrorResponseSchema.parse({
        message: "Sessão expirada. Atualize a página e tente novamente.",
      }),
    );
    return null;
  }

  const idHash = sessionHash(token);
  const session = await options.repository.findSession(idHash);
  if (!session) {
    clearSessionCookie(reply, options);
    reply.code(403).send(
      AuthErrorResponseSchema.parse({
        message: "Sessão expirada. Atualize a página e tente novamente.",
      }),
    );
    return null;
  }

  const csrfToken = request.headers["x-csrf-token"];
  if (typeof csrfToken !== "string" || !csrfMatches(session.csrfToken, csrfToken)) {
    reply
      .code(403)
      .send(AuthErrorResponseSchema.parse({ message: "Token de segurança inválido." }));
    return null;
  }

  const user =
    session.userId === null ? null : await options.repository.findUserById(session.userId);
  if (session.userId !== null && (!user || !user.active)) {
    await options.repository.deleteSession(idHash);
    clearSessionCookie(reply, options);
    reply.code(401).send(AuthErrorResponseSchema.parse({ message: "Sessão expirada." }));
    return null;
  }

  return { idHash, session, user };
}

function validationResponse(
  issues: Array<{ path: PropertyKey[]; message: string }>,
): ReturnType<typeof AuthValidationResponseSchema.parse> {
  return AuthValidationResponseSchema.parse({
    message: "Por favor, corrija os campos informados.",
    errors: issues.map((issue) => ({
      field: String(issue.path[0] ?? "form"),
      message: issue.message,
    })),
  });
}

export function registerAuthRoutes(app: FastifyInstance, options: AuthRouteOptions): void {
  app.register(cookie);
  const service = new AuthService(options.repository);

  app.get("/api/auth/me", async (request, reply) => {
    const resolved = await resolveSession(request, reply, options);
    const flashMessage = await options.repository.consumeFlashMessage(resolved.idHash);
    const response =
      resolved.user && resolved.session.userId !== null
        ? {
            authenticated: true as const,
            user: {
              id: resolved.user.id,
              name: resolved.user.name,
              username: resolved.user.username,
              isAdmin: resolved.user.isAdmin,
            },
            csrfToken: resolved.session.csrfToken,
            flashMessage,
          }
        : {
            authenticated: false as const,
            csrfToken: resolved.session.csrfToken,
            flashMessage,
          };
    return reply.code(200).send(CurrentUserResponseSchema.parse(response));
  });

  app.post(
    "/api/auth/login",
    { config: { rateLimit: { max: 5, timeWindow: 60_000 } } },
    async (request, reply) => {
      const parsed = LoginInputSchema.safeParse(request.body);
      if (!parsed.success) {
        return reply.code(400).send(validationResponse(parsed.error.issues));
      }

      const session = await requireMutationSession(request, reply, options);
      if (!session) {
        return reply;
      }

      let result: Awaited<ReturnType<AuthService["login"]>>;
      try {
        result = await service.login(parsed.data, request.ip);
      } catch (error) {
        request.log.error({ err: error }, "Login operation failed.");
        return reply.code(500).send(
          AuthErrorResponseSchema.parse({
            message: "Erro ao processar o login. Por favor, tente novamente mais tarde.",
          }),
        );
      }
      if (!result.success) {
        const banned = result.reason === "banned";
        return reply.code(banned ? 403 : 401).send(
          AuthErrorResponseSchema.parse({
            message: banned
              ? "Este usuário foi banido por violar as regras de conduta do Portal!"
              : "Usuário ou senha incorretos.",
          }),
        );
      }

      await options.repository.deleteSession(session.idHash);
      await issueSession(reply, options, result.user.id);
      return reply.code(200).send(
        AuthSuccessResponseSchema.parse({
          success: true,
          message: "Login realizado com sucesso.",
          user: result.user,
        }),
      );
    },
  );

  app.post(
    "/api/auth/register",
    { config: { rateLimit: { max: 3, timeWindow: 60_000 } } },
    async (request, reply) => {
      const parsed = RegistrationInputSchema.safeParse(request.body);
      if (!parsed.success) {
        return reply.code(400).send(validationResponse(parsed.error.issues));
      }

      const session = await requireMutationSession(request, reply, options);
      if (!session) {
        return reply;
      }

      let result: Awaited<ReturnType<AuthService["register"]>>;
      try {
        result = await service.register(parsed.data);
      } catch (error) {
        request.log.error({ err: error }, "Registration operation failed.");
        return reply.code(500).send(
          AuthErrorResponseSchema.parse({
            message: "Erro ao processar o cadastro. Por favor, tente novamente mais tarde.",
          }),
        );
      }
      if (!result.success) {
        return reply.code(409).send(
          AuthErrorResponseSchema.parse({
            message:
              result.field === "username"
                ? "Este nome de usuário já está em uso."
                : "Este e-mail já está cadastrado.",
          }),
        );
      }

      await options.repository.setFlashMessage(session.idHash, result.message);
      return reply
        .code(201)
        .send(AuthSuccessResponseSchema.parse({ success: true, message: result.message }));
    },
  );

  app.post("/api/auth/logout", async (request, reply) => {
    const session = await requireMutationSession(request, reply, options);
    if (!session) {
      return reply;
    }

    if (session.session.userId !== null) {
      try {
        await options.repository.recordLogout(session.session.userId);
      } catch (error) {
        request.log.error({ err: error }, "Logout audit update failed.");
      }
    }
    await options.repository.deleteSession(session.idHash);
    clearSessionCookie(reply, options);
    const issued = await issueSession(reply, options, null);
    await options.repository.setFlashMessage(issued.idHash, "Logout realizado com sucesso!");
    return reply.code(200).send(
      AuthSuccessResponseSchema.parse({
        success: true,
        message: "Logout realizado com sucesso!",
      }),
    );
  });

  app.post(
    "/api/auth/password-recovery",
    { config: { rateLimit: { max: 3, timeWindow: 60_000 } } },
    async (request, reply) => {
      const validation = PasswordRecoveryInputSchema.safeParse(request.body);
      if (!validation.success) {
        return reply.code(400).send(validationResponse(validation.error.issues));
      }
      return reply.code(501).send(
        AuthErrorResponseSchema.parse({
          message:
            "A recuperação de senha não está disponível: o sistema atual não envia e-mail nem persiste tokens.",
        }),
      );
    },
  );
}

export const authCookieName = SESSION_COOKIE;
