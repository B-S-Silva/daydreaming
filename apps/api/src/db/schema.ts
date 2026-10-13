import {
  boolean,
  char,
  index,
  int,
  mysqlTable,
  text,
  timestamp,
  uniqueIndex,
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

export const questoes = mysqlTable(
  "questoes",
  {
    id: int("id").autoincrement().primaryKey(),
    tipoProva: varchar("tipo_prova", { length: 20 }).notNull(),
    numeroQuestao: int("numero_questao").notNull(),
    enunciado: text("enunciado").notNull(),
    alternativaA: text("alternativa_a"),
    alternativaB: text("alternativa_b"),
    alternativaC: text("alternativa_c"),
    alternativaD: text("alternativa_d"),
    alternativaE: text("alternativa_e"),
    respostaCorreta: varchar("resposta_correta", { length: 10 }),
    tipoQuestao: varchar("tipo_questao", { length: 30 }).default("multipla_escolha"),
    respostaDissertativa: text("resposta_dissertativa"),
    dificuldade: varchar("dificuldade", { length: 10 }).default("medio"),
    materia: varchar("materia", { length: 100 }),
    assunto: varchar("assunto", { length: 100 }),
    explicacao: text("explicacao"),
    ativa: boolean("ativa").default(true),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).defaultNow(),
    updatedAt: timestamp("updated_at", { mode: "date" }),
  },
  (table) => [
    uniqueIndex("unique_tipo_numero").on(table.tipoProva, table.numeroQuestao),
    index("idx_questoes_ativa_tipo").on(table.ativa, table.tipoProva),
  ],
);
