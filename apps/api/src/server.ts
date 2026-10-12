import { createPool } from "mysql2/promise";
import { drizzle } from "drizzle-orm/mysql2";
import { createApp } from "./app.js";
import { MySqlAuthRepository } from "./auth/auth-repository.js";
import { readApiConfig } from "./config.js";
import * as schema from "./db/schema.js";

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
const app = createApp({
  logger: true,
  auth: {
    repository: new MySqlAuthRepository(db),
    sessionTtlSeconds: config.sessionTtlSeconds,
    cookieSecure: config.cookieSecure,
    webOrigin: config.webOrigin,
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
