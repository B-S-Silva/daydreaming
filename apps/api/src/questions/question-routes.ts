import { basename } from "node:path";
import multipart from "@fastify/multipart";
import { XMLParser, XMLValidator } from "fast-xml-parser";
import type { FastifyInstance, FastifyReply, FastifyRequest } from "fastify";
import {
  AuthErrorResponseSchema,
  QuestionImportResponseSchema,
  QuestionStatsResponseSchema,
} from "@daydreaming/shared";
import type { AuthRouteOptions, ResolvedSession } from "../auth/auth-routes.js";
import { requireMutationSession, resolveSession } from "../auth/auth-routes.js";
import type { QuestionRepository } from "./question-repository.js";
import { QuestionImportValidationError, QuestionService } from "./question-service.js";

const MAX_QUESTION_FILE_SIZE = 10 * 1024 * 1024;
const xmlParser = new XMLParser({
  ignoreAttributes: true,
  processEntities: false,
  isArray: (tagName) => tagName === "questao",
});

export interface QuestionRouteOptions {
  auth: AuthRouteOptions;
  repository: QuestionRepository;
}

function findQuestionArray(value: unknown, depth = 0): unknown[] | null {
  if (depth > 32) {
    return null;
  }
  if (Array.isArray(value)) {
    return value.some(
      (item) => typeof item === "object" && item !== null && "numero_questao" in item,
    )
      ? value
      : null;
  }
  if (typeof value !== "object" || value === null) {
    return null;
  }

  const record = value as Record<string, unknown>;
  if ("questoes" in record) {
    const nested = findQuestionArray(record.questoes, depth + 1);
    if (nested) {
      return nested;
    }
  }
  if ("questao" in record) {
    const nested = findQuestionArray(record.questao, depth + 1);
    if (nested) {
      return nested;
    }
    if (typeof record.questao === "object" && record.questao !== null) {
      return [record.questao];
    }
  }

  for (const child of Object.values(record)) {
    const nested = findQuestionArray(child, depth + 1);
    if (nested) {
      return nested;
    }
  }
  return null;
}

function normalizeQuestionNumbers(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(normalizeQuestionNumbers);
  }
  if (typeof value !== "object" || value === null) {
    return value;
  }

  return Object.fromEntries(
    Object.entries(value).map(([key, child]) => [
      key,
      key === "numero_questao" && typeof child === "string" && /^\d+$/.test(child)
        ? Number(child)
        : normalizeQuestionNumbers(child),
    ]),
  );
}

function normalizeXmlQuestions(questions: unknown[]): unknown[] {
  return questions.map(normalizeQuestionNumbers);
}

function parseQuestionDocument(buffer: Buffer, extension: string): unknown {
  const content = buffer.toString("utf8");
  if (extension === "json") {
    let document: unknown;
    try {
      document = JSON.parse(content) as unknown;
    } catch {
      throw new QuestionImportValidationError("JSON inválido.");
    }
    return normalizeQuestionNumbers(document);
  }

  const validation = XMLValidator.validate(content);
  if (validation !== true) {
    throw new QuestionImportValidationError("Erro ao carregar arquivo XML.");
  }

  const document = xmlParser.parse(content) as unknown;
  const questions = findQuestionArray(document);
  if (!questions) {
    throw new QuestionImportValidationError("Estrutura deve conter um array de questões");
  }
  return { questoes: normalizeXmlQuestions(questions) };
}

function questionStatsResponse(items: Awaited<ReturnType<QuestionRepository["listStats"]>>) {
  return QuestionStatsResponseSchema.parse({
    items: items.map((item) => ({
      ...item,
      ultima_atualizacao: item.ultima_atualizacao,
    })),
    total: items.reduce((total, item) => total + item.total, 0),
  });
}

async function requireAdmin(
  request: FastifyRequest,
  reply: FastifyReply,
  authOptions: AuthRouteOptions,
): Promise<ResolvedSession | null> {
  const session = await resolveSession(request, reply, authOptions);
  if (!session.user) {
    reply.code(401).send(AuthErrorResponseSchema.parse({ message: "Autenticação necessária." }));
    return null;
  }
  if (!session.user.isAdmin) {
    reply
      .code(403)
      .send(AuthErrorResponseSchema.parse({ message: "Acesso restrito a administradores." }));
    return null;
  }
  return session;
}

function firstFieldValue(fields: Record<string, unknown>, fieldName: string): unknown {
  const value = fields[fieldName];
  if (Array.isArray(value)) {
    return value[0] && typeof value[0] === "object" && "value" in value[0]
      ? value[0].value
      : undefined;
  }
  return typeof value === "object" && value !== null && "value" in value ? value.value : undefined;
}

export function registerQuestionRoutes(app: FastifyInstance, options: QuestionRouteOptions): void {
  app.register(multipart, {
    limits: { fileSize: MAX_QUESTION_FILE_SIZE, files: 1, fields: 2 },
  });
  const service = new QuestionService(options.repository);

  app.get("/api/admin/questions/stats", async (request, reply) => {
    if (!(await requireAdmin(request, reply, options.auth))) {
      return reply;
    }
    const response = questionStatsResponse(await options.repository.listStats());
    return reply.code(200).send(response);
  });

  app.get("/api/admin/questions/example", async (request, reply) => {
    if (!(await requireAdmin(request, reply, options.auth))) {
      return reply;
    }
    const query = request.query as { tipo_prova?: unknown };
    try {
      const result = await service.example(query.tipo_prova ?? "toefl");
      return reply
        .code(200)
        .header("content-type", "application/json; charset=utf-8")
        .header(
          "content-disposition",
          `attachment; filename="exemplo_questoes_${result.examType}.json"`,
        )
        .send(result.json);
    } catch (error) {
      if (error instanceof QuestionImportValidationError) {
        return reply.code(400).send(AuthErrorResponseSchema.parse({ message: error.message }));
      }
      throw error;
    }
  });

  app.post(
    "/api/admin/questions/import",
    { config: { rateLimit: { max: 5, timeWindow: 60_000 } } },
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
        const upload = await request.file();
        if (!upload || upload.fieldname !== "arquivo_questoes") {
          return reply
            .code(400)
            .send(AuthErrorResponseSchema.parse({ message: "Erro no upload do arquivo." }));
        }

        const extension = basename(upload.filename).split(".").pop()?.toLowerCase() ?? "";
        if (extension !== "json" && extension !== "xml") {
          return reply.code(400).send(
            AuthErrorResponseSchema.parse({
              message: "Apenas arquivos JSON e XML são permitidos.",
            }),
          );
        }

        const examType = firstFieldValue(upload.fields, "tipo_prova");
        const buffer = await upload.toBuffer();
        const parsedDocument = parseQuestionDocument(buffer, extension);
        const imported = await service.import(examType, parsedDocument);
        const response = QuestionImportResponseSchema.parse({
          success: true,
          questoes_inseridas: imported.inserted,
          questoes_atualizadas: imported.updated,
          total_processadas: imported.total,
          message: `Questões carregadas com sucesso! ${imported.inserted} inseridas, ${imported.updated} atualizadas.`,
        });
        return reply.code(200).send(response);
      } catch (error) {
        if (
          typeof error === "object" &&
          error !== null &&
          "code" in error &&
          error.code === "FST_REQ_FILE_TOO_LARGE"
        ) {
          return reply.code(413).send(
            AuthErrorResponseSchema.parse({
              message: "O arquivo excede o limite máximo de 10MB.",
            }),
          );
        }
        if (error instanceof QuestionImportValidationError) {
          return reply
            .code(400)
            .send(AuthErrorResponseSchema.parse({ message: `Arquivo inválido: ${error.message}` }));
        }
        request.log.error({ err: error }, "Question import failed.");
        return reply.code(500).send(
          AuthErrorResponseSchema.parse({
            message: "Erro ao carregar questões do arquivo.",
          }),
        );
      }
    },
  );
}
