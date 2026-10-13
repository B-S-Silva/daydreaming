import { sql } from "drizzle-orm";
import {
  boolean,
  char,
  datetime,
  decimal,
  index,
  int,
  mysqlEnum,
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

export const sessoesTeste = mysqlTable(
  "sessoes_teste",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    tipoProva: varchar("tipo_prova", { length: 20 }).notNull(),
    inicio: datetime("inicio", { mode: "date" }).notNull(),
    fim: datetime("fim", { mode: "date" }),
    duracaoMinutos: int("duracao_minutos").notNull(),
    questoesRespondidas: int("questoes_respondidas").notNull().default(0),
    status: mysqlEnum("status", ["ativo", "finalizado", "expirado"]).notNull().default("ativo"),
    pontuacaoFinal: decimal("pontuacao_final", { precision: 5, scale: 2 }),
    acertos: int("acertos").notNull().default(0),
    tempoGasto: int("tempo_gasto").notNull().default(0),
  },
  (table) => [
    index("idx_sessoes_teste_usuario_status").on(table.usuarioId, table.status),
    index("idx_sessoes_teste_usuario_inicio").on(table.usuarioId, table.inicio),
  ],
);

export const respostasUsuario = mysqlTable(
  "respostas_usuario",
  {
    id: int("id").autoincrement().primaryKey(),
    sessaoId: int("sessao_id")
      .notNull()
      .references(() => sessoesTeste.id, { onDelete: "cascade" }),
    questaoId: int("questao_id")
      .notNull()
      .references(() => questoes.id, { onDelete: "cascade" }),
    questaoNumero: int("questao_numero").notNull(),
    respostaUsuario: text("resposta_usuario"),
    respostaDissertativaUsuario: text("resposta_dissertativa_usuario"),
    respostaCorreta: text("resposta_correta"),
    estaCorreta: boolean("esta_correta").notNull().default(false),
    dataResposta: datetime("data_resposta", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`)
      .$onUpdate(() => new Date()),
  },
  (table) => [
    uniqueIndex("unique_resposta_sessao_questao").on(table.sessaoId, table.questaoNumero),
    index("idx_respostas_usuario_questao").on(table.questaoId),
  ],
);

export const resultadosTestes = mysqlTable(
  "resultados_testes",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    sessaoId: int("sessao_id")
      .notNull()
      .references(() => sessoesTeste.id, { onDelete: "cascade" }),
    tipoProva: varchar("tipo_prova", { length: 20 }).notNull(),
    pontuacao: decimal("pontuacao", { precision: 5, scale: 2 }).notNull(),
    acertos: int("acertos").notNull(),
    totalQuestoes: int("total_questoes").notNull(),
    questoesRespondidas: int("questoes_respondidas").notNull(),
    tempoGasto: int("tempo_gasto").notNull(),
    dataRealizacao: datetime("data_realizacao", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`),
  },
  (table) => [
    index("idx_resultados_testes_usuario_data").on(table.usuarioId, table.dataRealizacao),
    index("idx_resultados_testes_sessao").on(table.sessaoId),
  ],
);

export const forumCategorias = mysqlTable(
  "forum_categorias",
  {
    id: int("id").autoincrement().primaryKey(),
    nome: varchar("nome", { length: 100 }).notNull(),
    descricao: text("descricao"),
    cor: varchar("cor", { length: 7 }).notNull().default("#187bcd"),
    icone: varchar("icone", { length: 50 }).notNull().default("💬"),
    ativo: boolean("ativo").notNull().default(true),
    ordem: int("ordem").notNull().default(0),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [index("idx_forum_categorias_ativo_ordem").on(table.ativo, table.ordem, table.nome)],
);

export const forumTopicos = mysqlTable(
  "forum_topicos",
  {
    id: int("id").autoincrement().primaryKey(),
    categoriaId: int("categoria_id")
      .notNull()
      .references(() => forumCategorias.id, { onDelete: "restrict" }),
    autorId: int("autor_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    usuarioId: int("usuario_id").references(() => usuarios.id, { onDelete: "set null" }),
    titulo: varchar("titulo", { length: 255 }).notNull(),
    conteudo: text("conteudo").notNull(),
    visualizacoes: int("visualizacoes").notNull().default(0),
    fixado: boolean("fixado").notNull().default(false),
    fechado: boolean("fechado").notNull().default(false),
    aprovado: boolean("aprovado").notNull().default(true),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).notNull().defaultNow(),
    dataAtualizacao: timestamp("data_atualizacao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [
    index("idx_forum_topicos_categoria_atualizacao").on(table.categoriaId, table.dataAtualizacao),
    index("idx_forum_topicos_autor").on(table.autorId),
    index("idx_forum_topicos_usuario").on(table.usuarioId),
    index("idx_forum_topicos_moderacao").on(table.aprovado, table.fixado, table.fechado),
  ],
);

export const forumRespostas = mysqlTable(
  "forum_respostas",
  {
    id: int("id").autoincrement().primaryKey(),
    topicoId: int("topico_id")
      .notNull()
      .references(() => forumTopicos.id, { onDelete: "cascade" }),
    autorId: int("autor_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    usuarioId: int("usuario_id").references(() => usuarios.id, { onDelete: "set null" }),
    conteudo: text("conteudo").notNull(),
    aprovado: boolean("aprovado").notNull().default(true),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).notNull().defaultNow(),
    dataAtualizacao: timestamp("data_atualizacao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [
    index("idx_forum_respostas_topico_data").on(table.topicoId, table.dataCriacao),
    index("idx_forum_respostas_autor").on(table.autorId),
    index("idx_forum_respostas_usuario").on(table.usuarioId),
    index("idx_forum_respostas_aprovado").on(table.aprovado),
  ],
);

export const forumCurtidas = mysqlTable(
  "forum_curtidas",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    topicoId: int("topico_id").references(() => forumTopicos.id, { onDelete: "cascade" }),
    respostaId: int("resposta_id").references(() => forumRespostas.id, { onDelete: "cascade" }),
    tipoCurtida: varchar("tipo_curtida", { length: 10 }).notNull().default("like"),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [
    uniqueIndex("unique_curtida_topico").on(table.usuarioId, table.topicoId),
    uniqueIndex("unique_curtida_resposta").on(table.usuarioId, table.respostaId),
    index("idx_forum_curtidas_topico").on(table.topicoId),
    index("idx_forum_curtidas_resposta").on(table.respostaId),
  ],
);

export const forumModeracao = mysqlTable(
  "forum_moderacao",
  {
    id: int("id").autoincrement().primaryKey(),
    moderadorId: int("moderador_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    tipoConteudo: varchar("tipo_conteudo", { length: 20 }),
    conteudoId: int("conteudo_id"),
    topicoId: int("topico_id"),
    respostaId: int("resposta_id"),
    acao: varchar("acao", { length: 30 }).notNull(),
    motivo: text("motivo"),
    dataAcao: timestamp("data_acao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [
    index("idx_forum_moderacao_data").on(table.dataAcao),
    index("idx_forum_moderacao_alvo").on(table.tipoConteudo, table.conteudoId),
    index("idx_forum_moderacao_topico").on(table.topicoId),
    index("idx_forum_moderacao_resposta").on(table.respostaId),
  ],
);
