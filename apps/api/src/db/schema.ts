import {
  boolean,
  char,
  index,
  int,
  mysqlTable,
  text,
  timestamp,
  varchar,
} from "drizzle-orm/mysql-core";

export const usuarios = mysqlTable("usuarios", {
  id: int("id").autoincrement().primaryKey(),
  nome: varchar("nome", { length: 100 }).notNull(),
  usuario: varchar("usuario", { length: 50 }).notNull().unique(),
  email: varchar("email", { length: 100 }).notNull().unique(),
  senha: varchar("senha", { length: 255 }).notNull(),
  isAdmin: boolean("is_admin").default(false),
  ativo: boolean("ativo").default(true),
  dataCriacao: timestamp("data_criacao", { mode: "date" }).defaultNow(),
  ultimoAcesso: timestamp("ultimo_acesso", { mode: "date" }),
  ultimoLogout: timestamp("ultimo_logout", { mode: "date" }),
});

export const logsAcesso = mysqlTable("logs_acesso", {
  id: int("id").autoincrement().primaryKey(),
  usuarioId: int("usuario_id").references(() => usuarios.id, { onDelete: "set null" }),
  ip: varchar("ip", { length: 45 }).notNull(),
  sucesso: boolean("sucesso").notNull(),
});

export const logsSistema = mysqlTable("logs_sistema", {
  id: int("id").autoincrement().primaryKey(),
  usuarioId: int("usuario_id").references(() => usuarios.id, { onDelete: "set null" }),
  acao: varchar("acao", { length: 50 }).notNull(),
  detalhes: text("detalhes"),
  dataHora: timestamp("data_hora", { mode: "date" }),
  ip: varchar("ip", { length: 45 }),
});

export const nodeSessions = mysqlTable(
  "node_sessions",
  {
    idHash: char("id_hash", { length: 64 }).primaryKey(),
    usuarioId: int("usuario_id").references(() => usuarios.id, { onDelete: "cascade" }),
    csrfToken: char("csrf_token", { length: 64 }).notNull(),
    mensagem: varchar("mensagem", { length: 255 }),
    criadoEm: timestamp("criado_em", { mode: "date" }).notNull().defaultNow(),
    expiraEm: timestamp("expira_em", { mode: "date" }).notNull(),
  },
  (table) => [index("idx_node_sessions_expira").on(table.expiraEm)],
);
