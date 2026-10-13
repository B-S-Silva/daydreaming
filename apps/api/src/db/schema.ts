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

export const perfilUsuario = mysqlTable(
  "perfil_usuario",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    escola: varchar("escola", { length: 150 }),
    serie: varchar("serie", { length: 50 }),
    localizacao: varchar("localizacao", { length: 150 }),
    gpa: decimal("gpa", { precision: 3, scale: 2 }),
    idiomas: text("idiomas"),
    exames: text("exames"),
    interesses: varchar("interesses", { length: 255 }),
    biografia: text("biografia"),
    rastreamentoPaises: boolean("rastreamento_paises").notNull().default(false),
    dataAtualizacao: timestamp("data_atualizacao", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [uniqueIndex("unique_perfil_usuario").on(table.usuarioId)],
);

export const usuarioGpa = mysqlTable(
  "usuario_gpa",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    gpa: decimal("gpa", { precision: 3, scale: 2 }).notNull(),
    notasUtilizadas: text("notas_utilizadas").notNull(),
    dataCalculo: datetime("data_calculo", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`),
  },
  (table) => [
    index("idx_usuario_gpa_usuario_data").on(table.usuarioId, table.dataCalculo),
    index("idx_usuario_gpa_gpa").on(table.gpa),
  ],
);

export const historicoAtividades = mysqlTable(
  "historico_atividades",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    tipo: varchar("tipo", { length: 50 }).notNull(),
    descricao: varchar("descricao", { length: 255 }).notNull(),
    detalhes: text("detalhes"),
    pontos: int("pontos").notNull().default(0),
    dataAtividade: datetime("data_atividade", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`),
  },
  (table) => [
    index("idx_historico_atividades_usuario_data").on(table.usuarioId, table.dataAtividade),
  ],
);

export const paisesVisitados = mysqlTable(
  "paises_visitados",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    codigoPais: varchar("codigo_pais", { length: 10 }).notNull(),
    nomePais: varchar("nome_pais", { length: 100 }).notNull(),
    primeiraVisita: datetime("primeira_visita", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`),
    ultimaVisita: datetime("ultima_visita", { mode: "date" })
      .notNull()
      .default(sql`CURRENT_TIMESTAMP`),
    totalVisitas: int("total_visitas").notNull().default(1),
  },
  (table) => [
    uniqueIndex("unique_pais_visitado_usuario_codigo").on(table.usuarioId, table.codigoPais),
    index("idx_paises_visitados_usuario").on(table.usuarioId),
  ],
);

export const badges = mysqlTable("badges", {
  id: int("id").autoincrement().primaryKey(),
  codigo: varchar("codigo", { length: 50 }).notNull().unique(),
  nome: varchar("nome", { length: 100 }).notNull(),
  descricao: text("descricao").notNull(),
  icone: varchar("icone", { length: 10 }).notNull(),
  tipo: varchar("tipo", { length: 30 }).notNull(),
  categoria: varchar("categoria", { length: 30 }),
  condicaoValor: int("condicao_valor"),
  raridade: varchar("raridade", { length: 20 }),
  experienciaBonus: int("experiencia_bonus"),
  ativa: boolean("ativa").default(true),
  dataCriacao: timestamp("data_criacao", { mode: "date" }).defaultNow(),
});

export const usuarioBadges = mysqlTable(
  "usuario_badges",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    badgeId: int("badge_id")
      .notNull()
      .references(() => badges.id, { onDelete: "cascade" }),
    dataConquista: datetime("data_conquista", { mode: "date" }).notNull(),
    contexto: varchar("contexto", { length: 100 }),
    notificado: boolean("notificado").default(false),
    dataCriacao: timestamp("data_criacao", { mode: "date" }).defaultNow(),
  },
  (table) => [
    uniqueIndex("unique_usuario_badge").on(table.usuarioId, table.badgeId),
    index("idx_usuario_badges_data").on(table.usuarioId, table.dataConquista),
  ],
);

export const niveisUsuario = mysqlTable(
  "niveis_usuario",
  {
    id: int("id").autoincrement().primaryKey(),
    usuarioId: int("usuario_id")
      .notNull()
      .references(() => usuarios.id, { onDelete: "cascade" }),
    nivelAtual: int("nivel_atual").notNull().default(1),
    experienciaTotal: int("experiencia_total").notNull().default(0),
    experienciaNivel: int("experiencia_nivel").notNull().default(0),
    experienciaNecessaria: int("experiencia_necessaria").notNull().default(100),
    updatedAt: timestamp("updated_at", { mode: "date" }).notNull().defaultNow(),
  },
  (table) => [uniqueIndex("unique_niveis_usuario").on(table.usuarioId)],
);
