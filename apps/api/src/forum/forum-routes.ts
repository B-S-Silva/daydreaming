import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import {
  AuthErrorResponseSchema,
  ForumAdminDashboardResponseSchema,
  ForumCategoryListResponseSchema,
  ForumMutationResponseSchema,
  ForumTopicDetailResponseSchema,
  ForumTopicListResponseSchema,
} from "@daydreaming/shared";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { requireMutationSession, resolveSession } from "../auth/auth-routes.js";
import type { ForumRepository } from "./forum-repository.js";
import { ForumService, ForumValidationError } from "./forum-service.js";

export interface ForumRouteOptions {
  auth: AuthRouteOptions;
  repository: ForumRepository;
}

async function requireUser(
  request: FastifyRequest,
  reply: FastifyReply,
  auth: AuthRouteOptions,
): Promise<ResolvedSession | null> {
  const session = await resolveSession(request, reply, auth);
  if (!session.user) {
    reply.code(401).send(AuthErrorResponseSchema.parse({ message: "Autenticação necessária." }));
    return null;
  }
  return session;
}

async function requireAdmin(
  request: FastifyRequest,
  reply: FastifyReply,
  auth: AuthRouteOptions,
): Promise<ResolvedSession | null> {
  const session = await requireUser(request, reply, auth);
  if (!session) {
    return null;
  }
  if (!session.user?.isAdmin) {
    reply
      .code(403)
      .send(AuthErrorResponseSchema.parse({ message: "Acesso restrito a administradores." }));
    return null;
  }
  return session;
}

function sendFailure(
  request: FastifyRequest,
  reply: FastifyReply,
  error: unknown,
  genericMessage: string,
) {
  if (error instanceof ForumValidationError) {
    return reply.code(400).send(AuthErrorResponseSchema.parse({ message: error.message }));
  }
  request.log.error({ err: error }, "Forum operation failed.");
  return reply.code(500).send(AuthErrorResponseSchema.parse({ message: genericMessage }));
}

export function registerForumRoutes(app: FastifyInstance, options: ForumRouteOptions): void {
  const service = new ForumService(options.repository);

  app.get("/api/forum/categories", async (request, reply) => {
    if (!(await requireUser(request, reply, options.auth))) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(ForumCategoryListResponseSchema.parse({ items: await service.listCategories() }));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar as categorias.");
    }
  });

  app.get("/api/forum/topics", async (request, reply) => {
    if (!(await requireUser(request, reply, options.auth))) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(
          ForumTopicListResponseSchema.parse({ items: await service.listTopics(request.query) }),
        );
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar os tópicos.");
    }
  });

  app.get<{ Params: { id: string } }>("/api/forum/topics/:id", async (request, reply) => {
    if (!(await requireUser(request, reply, options.auth))) {
      return reply;
    }
    try {
      const detail = await service.readTopic(request.params.id);
      if (!detail) {
        return reply
          .code(404)
          .send(AuthErrorResponseSchema.parse({ message: "Tópico não encontrado." }));
      }
      return reply.code(200).send(ForumTopicDetailResponseSchema.parse(detail));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o tópico.");
    }
  });

  app.post("/api/forum/topics", async (request, reply) => {
    const session = await requireMutationSession(request, reply, options.auth);
    if (!session) {
      return reply;
    }
    if (!session.user) {
      return reply
        .code(401)
        .send(AuthErrorResponseSchema.parse({ message: "Autenticação necessária." }));
    }
    try {
      const message = await service.createTopic(session.user.id, request.body);
      return reply.code(201).send(ForumMutationResponseSchema.parse({ success: true, message }));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível criar o tópico.");
    }
  });

  app.post("/api/forum/replies", async (request, reply) => {
    const session = await requireMutationSession(request, reply, options.auth);
    if (!session) {
      return reply;
    }
    if (!session.user) {
      return reply
        .code(401)
        .send(AuthErrorResponseSchema.parse({ message: "Autenticação necessária." }));
    }
    try {
      const message = await service.createReply(session.user.id, request.body);
      return reply.code(201).send(ForumMutationResponseSchema.parse({ success: true, message }));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível adicionar a resposta.");
    }
  });

  app.post("/api/forum/likes", async (request, reply) => {
    const session = await requireMutationSession(request, reply, options.auth);
    if (!session) {
      return reply;
    }
    if (!session.user) {
      return reply
        .code(401)
        .send(AuthErrorResponseSchema.parse({ message: "Autenticação necessária." }));
    }
    try {
      await service.like(session.user.id, request.body);
      return reply.code(200).send(
        ForumMutationResponseSchema.parse({
          success: true,
          message: "Curtida registrada.",
        }),
      );
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível registrar a curtida.");
    }
  });

  app.get("/api/admin/forum/dashboard", async (request, reply) => {
    if (!(await requireAdmin(request, reply, options.auth))) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(ForumAdminDashboardResponseSchema.parse(await service.adminDashboard(request.query)));
    } catch (error) {
      return sendFailure(
        request,
        reply,
        error,
        "Não foi possível carregar o painel administrativo.",
      );
    }
  });

  app.post(
    "/api/admin/forum/categories",
    { config: { rateLimit: { max: 10, timeWindow: 60_000 } } },
    async (request, reply) => {
      const session = await requireMutationSession(request, reply, options.auth);
      if (!session) {
        return reply;
      }
      if (!session.user?.isAdmin) {
        return reply.code(session.user ? 403 : 401).send(
          AuthErrorResponseSchema.parse({
            message: session.user
              ? "Acesso restrito a administradores."
              : "Autenticação necessária.",
          }),
        );
      }
      try {
        const message = await service.createCategory(request.body);
        return reply.code(201).send(ForumMutationResponseSchema.parse({ success: true, message }));
      } catch (error) {
        return sendFailure(request, reply, error, "Não foi possível criar a categoria.");
      }
    },
  );

  app.post(
    "/api/admin/forum/moderation",
    { config: { rateLimit: { max: 10, timeWindow: 60_000 } } },
    async (request, reply) => {
      const session = await requireMutationSession(request, reply, options.auth);
      if (!session) {
        return reply;
      }
      if (!session.user?.isAdmin) {
        return reply.code(session.user ? 403 : 401).send(
          AuthErrorResponseSchema.parse({
            message: session.user
              ? "Acesso restrito a administradores."
              : "Autenticação necessária.",
          }),
        );
      }
      try {
        const result = await service.moderate(session.user.id, request.body);
        if (!result.found) {
          return reply
            .code(404)
            .send(AuthErrorResponseSchema.parse({ message: "Conteúdo não encontrado." }));
        }
        return reply.code(200).send(
          ForumMutationResponseSchema.parse({
            success: true,
            message: result.message,
          }),
        );
      } catch (error) {
        return sendFailure(request, reply, error, "Não foi possível moderar o conteúdo.");
      }
    },
  );

  app.post(
    "/api/admin/forum/users/action",
    { config: { rateLimit: { max: 10, timeWindow: 60_000 } } },
    async (request, reply) => {
      const session = await requireMutationSession(request, reply, options.auth);
      if (!session) {
        return reply;
      }
      if (!session.user?.isAdmin) {
        return reply.code(session.user ? 403 : 401).send(
          AuthErrorResponseSchema.parse({
            message: session.user
              ? "Acesso restrito a administradores."
              : "Autenticação necessária.",
          }),
        );
      }
      try {
        const result = await service.changeUser(session.user.id, request.body);
        if (!result.found) {
          return reply
            .code(400)
            .send(
              AuthErrorResponseSchema.parse({ message: "Não é possível alterar esse usuário." }),
            );
        }
        return reply.code(200).send(
          ForumMutationResponseSchema.parse({
            success: true,
            message: result.message,
          }),
        );
      } catch (error) {
        return sendFailure(request, reply, error, "Não foi possível alterar o usuário.");
      }
    },
  );
}
