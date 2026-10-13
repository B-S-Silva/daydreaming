import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import {
  AuthErrorResponseSchema,
  SimulatorAnswerResponseSchema,
  SimulatorDashboardResponseSchema,
  SimulatorErrorResponseSchema,
  SimulatorFinishResponseSchema,
  SimulatorSessionResponseSchema,
  SimulatorStartInputSchema,
  SimulatorStartResponseSchema,
} from "@daydreaming/shared";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { requireMutationSession, resolveSession } from "../auth/auth-routes.js";
import type { SimulatorRepository } from "./simulator-service.js";
import { SimulatorService, SimulatorValidationError } from "./simulator-service.js";

export interface SimulatorRouteOptions {
  auth: AuthRouteOptions;
  repository: SimulatorRepository;
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

async function requireMutationUser(
  request: FastifyRequest,
  reply: FastifyReply,
  auth: AuthRouteOptions,
): Promise<ResolvedSession | null> {
  const session = await requireMutationSession(request, reply, auth);
  if (!session) {
    return null;
  }
  if (!session.user) {
    reply
      .code(401)
      .send(
        SimulatorErrorResponseSchema.parse({ success: false, error: "Usuário não autenticado" }),
      );
    return null;
  }
  return session;
}

function sendFailure(
  request: FastifyRequest,
  reply: FastifyReply,
  error: unknown,
  genericMessage: string,
  legacyFinishShape = false,
) {
  if (error instanceof SimulatorValidationError) {
    return legacyFinishShape
      ? reply.code(error.statusCode).send({ erro: error.message })
      : reply
          .code(error.statusCode)
          .send(SimulatorErrorResponseSchema.parse({ success: false, error: error.message }));
  }
  request.log.error({ err: error }, "Simulator operation failed.");
  return legacyFinishShape
    ? reply.code(500).send({ erro: genericMessage })
    : reply
        .code(500)
        .send(SimulatorErrorResponseSchema.parse({ success: false, error: genericMessage }));
}

export function registerSimulatorRoutes(
  app: FastifyInstance,
  options: SimulatorRouteOptions,
): void {
  const service = new SimulatorService(options.repository);

  app.get("/api/simulator/dashboard", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(SimulatorDashboardResponseSchema.parse(await service.dashboard(session.user.id)));
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o simulador.");
    }
  });

  app.post("/api/simulator/sessions", async (request, reply) => {
    const session = await requireMutationUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    const validation = SimulatorStartInputSchema.safeParse(request.body);
    if (!validation.success) {
      return reply.code(400).send(
        SimulatorErrorResponseSchema.parse({
          success: false,
          error: "Tipo de prova inválido.",
        }),
      );
    }
    try {
      const started = await service.start(session.user.id, validation.data);
      return reply.code(201).send(
        SimulatorStartResponseSchema.parse({
          sessao_id: started.sessionId,
          tipo_prova: started.exam.tipo_prova,
        }),
      );
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível iniciar o teste.");
    }
  });

  app.get<{ Params: { id: string } }>("/api/simulator/sessions/:id", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(
          SimulatorSessionResponseSchema.parse(
            await service.loadSession(session.user.id, request.params.id),
          ),
        );
    } catch (error) {
      return sendFailure(request, reply, error, "Não foi possível carregar o teste.");
    }
  });

  app.post("/api/simulator/answers", async (request, reply) => {
    const session = await requireMutationUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      const result = await service.saveAnswer(session.user.id, request.ip, request.body);
      return reply.code(200).send(SimulatorAnswerResponseSchema.parse(result));
    } catch (error) {
      return sendFailure(request, reply, error, "Erro interno do servidor");
    }
  });

  app.post("/api/simulator/finish", async (request, reply) => {
    const session = await requireMutationUser(request, reply, options.auth);
    if (!session?.user) {
      return reply;
    }
    try {
      return reply
        .code(200)
        .send(
          SimulatorFinishResponseSchema.parse(await service.finish(session.user.id, request.body)),
        );
    } catch (error) {
      return sendFailure(request, reply, error, "Erro interno do servidor", true);
    }
  });
}
