import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import {
  AuthErrorResponseSchema,
  SessionHistoryResponseSchema,
  TestHistoryResponseSchema,
  TestResultResponseSchema,
  TestReviewResponseSchema,
} from "@daydreaming/shared";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { resolveSession } from "../auth/auth-routes.js";
import type { ResultsRepository } from "./results-repository.js";
import { ResultsNotFoundError, ResultsService } from "./results-service.js";

export interface ResultsRouteOptions {
  auth: AuthRouteOptions;
  repository: ResultsRepository;
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

function sendFailure(
  request: FastifyRequest,
  reply: FastifyReply,
  error: unknown,
  message: string,
) {
  if (error instanceof ResultsNotFoundError) {
    return reply
      .code(error.statusCode)
      .send(AuthErrorResponseSchema.parse({ message: error.message }));
  }
  request.log.error({ err: error }, "Results request failed.");
  return reply.code(500).send(AuthErrorResponseSchema.parse({ message }));
}

export function registerResultsRoutes(app: FastifyInstance, options: ResultsRouteOptions): void {
  const service = new ResultsService(options.repository);

  app.get<{ Params: { id: string } }>("/api/results/:id", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      const result = await service.getResult(session.user.id, request.params.id);
      return reply.code(200).send(TestResultResponseSchema.parse(result));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o resultado.");
    }
  });

  app.get<{ Params: { id: string } }>("/api/results/:id/review", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      const review = await service.getReview(session.user.id, request.params.id);
      return reply.code(200).send(TestReviewResponseSchema.parse(review));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar a revisão da prova.");
    }
  });

  app.get("/api/results/history/tests", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      const history = await service.getTestHistory(session.user.id);
      return reply.code(200).send(TestHistoryResponseSchema.parse(history));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o histórico de testes.");
    }
  });

  app.get("/api/results/history/sessions", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      const history = await service.getSessionHistory(session.user.id);
      return reply.code(200).send(SessionHistoryResponseSchema.parse(history));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o histórico de provas.");
    }
  });
}
