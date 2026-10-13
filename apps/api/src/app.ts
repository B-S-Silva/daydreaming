import Fastify from "fastify";
import rateLimit from "@fastify/rate-limit";
import { AuthErrorResponseSchema, HealthResponseSchema } from "@daydreaming/shared";
import type { AuthRouteOptions } from "./auth/auth-routes.js";
import { registerAuthRoutes } from "./auth/auth-routes.js";
import { getInternationalExamCatalog } from "./public/international-exams.js";
import { findCountryGuide, listCountryGuides } from "./public/country-guides.js";
import { registerUniversityRankingRoutes } from "./public/university-ranking-routes.js";
import type { QuestionRouteOptions } from "./questions/question-routes.js";
import { registerQuestionRoutes } from "./questions/question-routes.js";
import type { ForumRouteOptions } from "./forum/forum-routes.js";
import { registerForumRoutes } from "./forum/forum-routes.js";
import type { SimulatorRouteOptions } from "./simulators/simulator-routes.js";
import { registerSimulatorRoutes } from "./simulators/simulator-routes.js";
import type { ResultsRouteOptions } from "./results/results-routes.js";
import { registerResultsRoutes } from "./results/results-routes.js";
import type { StudyRouteOptions } from "./study/study-routes.js";
import { registerStudyRoutes } from "./study/study-routes.js";
import type { NotificationRouteOptions } from "./notifications/notification-routes.js";
import { registerNotificationRoutes } from "./notifications/notification-routes.js";

export interface CreateAppOptions {
  auth?: AuthRouteOptions;
  questions?: QuestionRouteOptions;
  forum?: ForumRouteOptions;
  simulator?: SimulatorRouteOptions;
  results?: ResultsRouteOptions;
  study?: StudyRouteOptions;
  notifications?: NotificationRouteOptions;
  logger?: boolean;
}

export function createApp(options: CreateAppOptions = {}) {
  const app = Fastify({ logger: options.logger ?? false });

  if (
    options.auth ||
    options.questions ||
    options.forum ||
    options.simulator ||
    options.results ||
    options.study ||
    options.notifications
  ) {
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

  app.get("/api/public/country-guides", async (_request, reply) => {
    return reply.code(200).send(listCountryGuides());
  });

  app.get<{ Params: { id: string } }>("/api/public/country-guides/:id", async (request, reply) => {
    const guide = findCountryGuide(request.params.id);
    if (!guide) {
      return reply
        .code(404)
        .send(AuthErrorResponseSchema.parse({ message: "Guia de país não encontrado." }));
    }
    return reply.code(200).send(guide);
  });

  registerUniversityRankingRoutes(app);

  if (
    options.auth ||
    options.questions ||
    options.forum ||
    options.simulator ||
    options.results ||
    options.study ||
    options.notifications
  ) {
    app.register(async (authApp) => {
      if (options.auth) {
        registerAuthRoutes(authApp, options.auth);
      }
      if (options.questions) {
        registerQuestionRoutes(authApp, options.questions);
      }
      if (options.forum) {
        registerForumRoutes(authApp, options.forum);
      }
      if (options.simulator) {
        registerSimulatorRoutes(authApp, options.simulator);
      }
      if (options.results) {
        registerResultsRoutes(authApp, options.results);
      }
      if (options.study) {
        registerStudyRoutes(authApp, options.study);
      }
      if (options.notifications) {
        registerNotificationRoutes(authApp, options.notifications);
      }
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
