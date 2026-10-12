import Fastify from "fastify";
import rateLimit from "@fastify/rate-limit";
import { AuthErrorResponseSchema, HealthResponseSchema } from "@daydreaming/shared";
import type { AuthRouteOptions } from "./auth/auth-routes.js";
import { registerAuthRoutes } from "./auth/auth-routes.js";
import { getInternationalExamCatalog } from "./public/international-exams.js";

export interface CreateAppOptions {
  auth?: AuthRouteOptions;
  logger?: boolean;
}

export function createApp(options: CreateAppOptions = {}) {
  const app = Fastify({ logger: options.logger ?? false });

  if (options.auth) {
    app.register(rateLimit, {
      global: false,
      errorResponseBuilder: (_request, context) =>
        Object.assign(new Error("Muitas tentativas. Aguarde antes de tentar novamente."), {
          statusCode: context.statusCode,
        }),
    });
  }

  app.get("/health", async (_request, reply) => {
    const response = HealthResponseSchema.parse({ status: "ok" });
    return reply.code(200).send(response);
  });

  app.get("/api/public/international-exams", async (_request, reply) => {
    return reply.code(200).send(getInternationalExamCatalog());
  });

  if (options.auth) {
    const authOptions = options.auth;
    app.register(async (authApp) => {
      registerAuthRoutes(authApp, authOptions);
    });
  }

  app.setErrorHandler((error, request, reply) => {
    if (error instanceof Error && "statusCode" in error && error.statusCode === 429) {
      return reply.code(429).send(AuthErrorResponseSchema.parse({ message: error.message }));
    }
    request.log.error({ err: error }, "Request failed");
    return reply.code(500).send({ message: "Erro interno do servidor." });
  });

  return app;
}
