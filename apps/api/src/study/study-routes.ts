import {
  AuthErrorResponseSchema,
  CountryExplorationResponseSchema,
  SaveGpaResponseSchema,
  StudentProfileResponseSchema,
  StudyMutationResponseSchema,
  UserBadgesResponseSchema,
} from "@daydreaming/shared";
import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { requireMutationSession, resolveSession } from "../auth/auth-routes.js";
import type { StudyRepository } from "./study-repository.js";
import { StudyService, StudyValidationError } from "./study-service.js";

export interface StudyRouteOptions {
  auth: AuthRouteOptions;
  repository: StudyRepository;
}

type AuthenticatedSession = Omit<ResolvedSession, "user" | "session"> & {
  user: NonNullable<ResolvedSession["user"]>;
  session: ResolvedSession["session"] & { userId: number };
};

async function requireUser(
  request: FastifyRequest,
  reply: FastifyReply,
  auth: AuthRouteOptions,
  mutation: boolean,
): Promise<AuthenticatedSession | null> {
  const session = mutation
    ? await requireMutationSession(request, reply, auth)
    : await resolveSession(request, reply, auth);
  if (!session) {
    return null;
  }
  if (!session.user || session.session.userId === null) {
    reply
      .code(401)
      .send(AuthErrorResponseSchema.parse({ message: "Faça login para acessar seu perfil." }));
    return null;
  }
  return {
    idHash: session.idHash,
    user: session.user,
    session: { ...session.session, userId: session.session.userId },
  };
}

export function registerStudyRoutes(app: FastifyInstance, options: StudyRouteOptions): void {
  const service = new StudyService(options.repository);

  app.get("/api/profile/me", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, false);
    if (!session) {
      return reply;
    }
    const profile = await service.getProfile(session.session.userId);
    return reply.code(200).send(StudentProfileResponseSchema.parse({ profile }));
  });

  app.get("/api/profile/badges", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, false);
    if (!session) {
      return reply;
    }
    const items = await options.repository.listBadges(session.session.userId);
    return reply.code(200).send(UserBadgesResponseSchema.parse({ items }));
  });

  app.put("/api/profile/me", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, true);
    if (!session) {
      return reply;
    }
    try {
      const message = await service.updateProfile(session.session.userId, request.body);
      return reply.code(200).send(StudyMutationResponseSchema.parse({ success: true, message }));
    } catch (error) {
      if (error instanceof StudyValidationError) {
        return reply.code(400).send(AuthErrorResponseSchema.parse({ message: error.message }));
      }
      throw error;
    }
  });

  app.post("/api/study/gpa", async (request, reply) => {
    const session = await requireUser(request, reply, options.auth, true);
    if (!session) {
      return reply;
    }
    try {
      const message = await service.saveGpa(session.session.userId, request.body);
      return reply.code(201).send(SaveGpaResponseSchema.parse({ success: true, message }));
    } catch (error) {
      if (error instanceof StudyValidationError) {
        return reply.code(400).send(AuthErrorResponseSchema.parse({ message: error.message }));
      }
      throw error;
    }
  });

  app.post<{ Params: { id: string } }>(
    "/api/profile/explored-countries/:id",
    async (request, reply) => {
      const session = await requireUser(request, reply, options.auth, true);
      if (!session) {
        return reply;
      }
      try {
        const result = await service.trackCountry(session.session.userId, request.params.id);
        return reply.code(200).send(CountryExplorationResponseSchema.parse(result));
      } catch (error) {
        if (error instanceof StudyValidationError) {
          return reply.code(400).send(AuthErrorResponseSchema.parse({ message: error.message }));
        }
        throw error;
      }
    },
  );
}
