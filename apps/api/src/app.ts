import Fastify from "fastify";
import { HealthResponseSchema } from "@daydreaming/shared";
import { getInternationalExamCatalog } from "./public/international-exams.js";

export function createApp() {
  const app = Fastify({ logger: false });

  app.get("/health", async (_request, reply) => {
    const response = HealthResponseSchema.parse({ status: "ok" });
    return reply.code(200).send(response);
  });

  app.get("/api/public/international-exams", async (_request, reply) => {
    return reply.code(200).send(getInternationalExamCatalog());
  });

  return app;
}
