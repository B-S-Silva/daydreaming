import { z } from "zod";

const DatabaseConfigSchema = z.object({
  host: z.string().min(1),
  port: z.number().int().min(1).max(65_535),
  database: z.string().min(1),
  user: z.string().min(1, "DATABASE_USER é obrigatório."),
  password: z.string().min(1, "DATABASE_PASSWORD é obrigatório."),
  charset: z.string().min(1),
});

const ApiConfigSchema = z.object({
  nodeEnv: z.enum(["development", "test", "production"]),
  apiPort: z.number().int().min(1).max(65_535),
  webOrigin: z.url(),
  sessionTtlSeconds: z.number().int().min(60).max(604_800),
  cookieSecure: z.boolean(),
  database: DatabaseConfigSchema,
});

export type ApiConfig = z.infer<typeof ApiConfigSchema>;

function parseBoolean(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined) {
    return fallback;
  }
  if (value === "true") {
    return true;
  }
  if (value === "false") {
    return false;
  }
  throw new Error("COOKIE_SECURE deve ser 'true' ou 'false'.");
}

export function readApiConfig(environment: NodeJS.ProcessEnv): ApiConfig {
  const nodeEnv = environment.NODE_ENV ?? "development";
  return ApiConfigSchema.parse({
    nodeEnv,
    apiPort: Number(environment.API_PORT ?? 3001),
    webOrigin: environment.WEB_ORIGIN ?? "http://localhost:5173",
    sessionTtlSeconds: Number(environment.SESSION_TTL_SECONDS ?? 3600),
    cookieSecure: parseBoolean(environment.COOKIE_SECURE, nodeEnv === "production"),
    database: {
      host: environment.DATABASE_HOST ?? "localhost",
      port: Number(environment.DATABASE_PORT ?? 3306),
      database: environment.DATABASE_NAME ?? "db_daydreamming_project",
      user: environment.DATABASE_USER ?? "",
      password: environment.DATABASE_PASSWORD ?? "",
      charset: environment.DATABASE_CHARSET ?? "utf8mb4",
    },
  });
}
