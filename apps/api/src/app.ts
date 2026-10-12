import Fastify from "fastify";
import { HealthResponseSchema } from "@daydreaming/shared";

export function createApp() {
  const app = Fastify({ logger: false });

  app.get("/health", async (_request, reply) => {
    const response = HealthResponseSchema.parse({ status: "ok" });
    return reply.code(200).send(response);
  });

  return app;
}
