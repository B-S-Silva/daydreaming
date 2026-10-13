import {
  AuthErrorResponseSchema,
  NotificationListResponseSchema,
  NotificationMutationResponseSchema,
} from "@daydreaming/shared";
import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { requireMutationSession, resolveSession } from "../auth/auth-routes.js";
import type { NotificationRepository } from "./notification-repository.js";

export interface NotificationRouteOptions {
  auth: AuthRouteOptions;
  repository: NotificationRepository;
}

async function requireUser(
  request: FastifyRequest,
  reply: FastifyReply,
  auth: AuthRouteOptions,
  mutation: boolean,
): Promise<ResolvedSession | null> {
  const session = mutation
    ? await requireMutationSession(request, reply, auth)
    : await resolveSession(request, reply, auth);
  if (!session) {
    return null;
  }
  if (!session.user || session.session.userId === null) {
    reply
      .code(401)
      .send(AuthErrorResponseSchema.parse({ message: "Faça login para ver suas notificações." }));
    return null;
  }
  return session;
}

export function registerNotificationRoutes(
  app: FastifyInstance,
  options: NotificationRouteOptions,
): void {
  app.get("/api/notifications", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, false);
    if (!session?.user) {
      return reply;
    }
    const query =
      typeof request.query === "object" && request.query !== null
        ? (request.query as Record<string, unknown>)
        : {};
    const limitValue = query.limit === undefined ? "10" : query.limit;
    if (typeof limitValue !== "string" || !/^[1-9]\d{0,2}$/.test(limitValue)) {
      return reply
        .code(400)
        .send(AuthErrorResponseSchema.parse({ message: "Limite de notificações inválido." }));
    }
    const limit = Number(limitValue);
    if (limit > 100) {
      return reply
        .code(400)
        .send(AuthErrorResponseSchema.parse({ message: "Limite de notificações inválido." }));
    }
    const scope = query.scope ?? "unread";
    if (scope !== "unread" && scope !== "all") {
      return reply
        .code(400)
        .send(AuthErrorResponseSchema.parse({ message: "Filtro de notificações inválido." }));
    }
    const [items, unreadCount] = await Promise.all([
      options.repository.list(session.user.id, limit, scope === "unread"),
      options.repository.unreadCount(session.user.id),
    ]);
    return reply
      .code(200)
      .send(NotificationListResponseSchema.parse({ items, unreadCount }));
  });

  app.post<{ Params: { id: string } }>("/api/notifications/:id/read", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, true);
    if (!session?.user) {
      return reply;
    }
    if (!/^[1-9]\d*$/.test(request.params.id) || !Number.isSafeInteger(Number(request.params.id))) {
      return reply
        .code(400)
        .send(AuthErrorResponseSchema.parse({ message: "ID de notificação inválido." }));
    }
    const marked = await options.repository.markRead(session.user.id, Number(request.params.id));
    if (!marked) {
      return reply
        .code(404)
        .send(AuthErrorResponseSchema.parse({ message: "Notificação não encontrada." }));
    }
    return reply.code(200).send(
      NotificationMutationResponseSchema.parse({
        success: true,
        message: "Notificação marcada como lida.",
      }),
    );
  });

  app.post("/api/notifications/read-all", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, true);
    if (!session?.user) {
      return reply;
    }
    await options.repository.markAllRead(session.user.id);
    return reply.code(200).send(
      NotificationMutationResponseSchema.parse({
        success: true,
        message: "Todas as notificações foram marcadas como lidas.",
      }),
    );
  });
}
