import { existsSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { createPool } from "mysql2/promise";
import { drizzle } from "drizzle-orm/mysql2";
import { createApp } from "./app.js";
import { MySqlAuthRepository } from "./auth/auth-repository.js";
import { MySqlQuestionRepository } from "./questions/question-repository.js";
import { MySqlForumRepository } from "./forum/forum-repository.js";
import { MySqlSimulatorRepository } from "./simulators/simulator-repository.js";
import { MySqlResultsRepository } from "./results/results-repository.js";
import { MySqlStudyRepository } from "./study/study-repository.js";
import { MySqlNotificationRepository } from "./notifications/notification-repository.js";
import { readApiConfig } from "./config.js";
import * as schema from "./db/schema.js";

const apiDirectory = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const envPath = resolve(apiDirectory, "../../.env");
if (existsSync(envPath)) {
  process.loadEnvFile(envPath);
}

const config = readApiConfig(process.env);
const pool = createPool({
  host: config.database.host,
  port: config.database.port,
  database: config.database.database,
  user: config.database.user,
  password: config.database.password,
  charset: config.database.charset,
  waitForConnections: true,
  connectionLimit: 10,
});
const db = drizzle(pool, { schema, mode: "default" });
const authOptions = {
  repository: new MySqlAuthRepository(db),
  sessionTtlSeconds: config.sessionTtlSeconds,
  cookieSecure: config.cookieSecure,
  webOrigin: config.webOrigin,
};
const app = createApp({
  logger: true,
  auth: authOptions,
  questions: {
    auth: authOptions,
    repository: new MySqlQuestionRepository(db),
  },
  forum: {
    auth: authOptions,
    repository: new MySqlForumRepository(db),
  },
  simulator: {
    auth: authOptions,
    repository: new MySqlSimulatorRepository(db),
  },
  results: {
    auth: authOptions,
    repository: new MySqlResultsRepository(db),
  },
  study: {
    auth: authOptions,
    repository: new MySqlStudyRepository(db),
  },
  notifications: {
    auth: authOptions,
    repository: new MySqlNotificationRepository(db),
  },
});

try {
  await pool.query("SELECT 1");
  await app.listen({ host: "0.0.0.0", port: config.apiPort });
} catch (error) {
  app.log.error({ err: error }, "API startup failed.");
  await pool.end();
  process.exitCode = 1;
}

for (const signal of ["SIGINT", "SIGTERM"] as const) {
  process.once(signal, async () => {
    await app.close();
    await pool.end();
  });
}
