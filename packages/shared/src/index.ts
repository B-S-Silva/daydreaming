import { z } from "zod";

export const HealthResponseSchema = z.object({ status: z.literal("ok") });
export type HealthResponse = z.infer<typeof HealthResponseSchema>;

export const ContinentSchema = z.enum(["america", "europa", "asia", "oceania", "africa"]);

export const ExamCountrySchema = z.object({
  id: z.string(),
  name: z.string(),
  flag: z.string(),
  continent: ContinentSchema,
  requirements: z.array(z.string()),
});

export const ExamSummarySchema = z.object({
  id: z.string(),
  name: z.string(),
  subtitle: z.string(),
  audience: z.string(),
  format: z.string(),
  noteLabel: z.string(),
  note: z.string(),
});

export const RegionalGuideSchema = z.object({
  id: z.string(),
  country: z.string(),
  requirements: z.array(z.string()),
});

export const PreparationTipSchema = z.object({
  title: z.string(),
  description: z.string(),
});

export const InternationalExamCatalogSchema = z.object({
  countries: z.array(ExamCountrySchema),
  exams: z.array(ExamSummarySchema),
  regionalGuides: z.array(RegionalGuideSchema),
  tips: z.array(PreparationTipSchema),
});

export type Continent = z.infer<typeof ContinentSchema>;
export type ExamCountry = z.infer<typeof ExamCountrySchema>;
export type ExamSummary = z.infer<typeof ExamSummarySchema>;
export type RegionalGuide = z.infer<typeof RegionalGuideSchema>;
export type PreparationTip = z.infer<typeof PreparationTipSchema>;
export type InternationalExamCatalog = z.infer<typeof InternationalExamCatalogSchema>;

function trimPhpInput(value: string): string {
  let start = 0;
  let end = value.length;
  const isTrimCharacter = (character: string): boolean =>
    [0, 9, 10, 11, 13, 32].includes(character.charCodeAt(0));

  while (start < end && isTrimCharacter(value[start]!)) {
    start += 1;
  }
  while (end > start && isTrimCharacter(value[end - 1]!)) {
    end -= 1;
  }

  return value.slice(start, end);
}

function isPhpEmpty(value: string): boolean {
  return value === "" || value === "0";
}

function phpStringByteLength(value: string): number {
  return new TextEncoder().encode(value).length;
}

const PhpTrimmedStringSchema = z.string().transform(trimPhpInput);

export const LoginInputSchema = z
  .object({
    username: PhpTrimmedStringSchema,
    password: PhpTrimmedStringSchema,
  })
  .superRefine((input, context) => {
    if (isPhpEmpty(input.username) || isPhpEmpty(input.password)) {
      context.addIssue({
        code: "custom",
        path: ["root"],
        message: "Por favor, preencha todos os campos.",
      });
    }
  });

export const RegistrationInputSchema = z
  .object({
    name: PhpTrimmedStringSchema,
    username: PhpTrimmedStringSchema,
    email: PhpTrimmedStringSchema,
    password: PhpTrimmedStringSchema,
    confirmPassword: PhpTrimmedStringSchema,
  })
  .superRefine((input, context) => {
    if (isPhpEmpty(input.name)) {
      context.addIssue({
        code: "custom",
        path: ["name"],
        message: "O nome é obrigatório.",
      });
    }

    if (isPhpEmpty(input.username)) {
      context.addIssue({
        code: "custom",
        path: ["username"],
        message: "O nome de usuário é obrigatório.",
      });
    } else if (phpStringByteLength(input.username) < 4) {
      context.addIssue({
        code: "custom",
        path: ["username"],
        message: "O nome de usuário deve ter pelo menos 4 caracteres.",
      });
    }

    if (isPhpEmpty(input.email)) {
      context.addIssue({
        code: "custom",
        path: ["email"],
        message: "O e-mail é obrigatório.",
      });
    } else if (!z.email().safeParse(input.email).success) {
      context.addIssue({
        code: "custom",
        path: ["email"],
        message: "Por favor, insira um e-mail válido.",
      });
    }

    if (isPhpEmpty(input.password)) {
      context.addIssue({
        code: "custom",
        path: ["password"],
        message: "A senha é obrigatória.",
      });
    } else if (phpStringByteLength(input.password) < 6) {
      context.addIssue({
        code: "custom",
        path: ["password"],
        message: "A senha deve ter pelo menos 6 caracteres.",
      });
    }

    if (input.password !== input.confirmPassword) {
      context.addIssue({
        code: "custom",
        path: ["confirmPassword"],
        message: "As senhas não coincidem.",
      });
    }
  });

export const PasswordRecoveryInputSchema = z
  .object({ email: PhpTrimmedStringSchema })
  .superRefine(({ email }, context) => {
    if (isPhpEmpty(email)) {
      context.addIssue({
        code: "custom",
        path: ["email"],
        message: "Por favor, informe seu e-mail.",
      });
    } else if (!z.email().safeParse(email).success) {
      context.addIssue({
        code: "custom",
        path: ["email"],
        message: "Por favor, insira um e-mail válido.",
      });
    }
  });

export const AuthenticatedUserSchema = z.object({
  id: z.number().int().positive(),
  name: z.string(),
  username: z.string(),
  isAdmin: z.boolean(),
});

export const CurrentUserResponseSchema = z.discriminatedUnion("authenticated", [
  z.object({
    authenticated: z.literal(true),
    user: AuthenticatedUserSchema,
    csrfToken: z.string().length(64),
    flashMessage: z.string().nullable(),
  }),
  z.object({
    authenticated: z.literal(false),
    csrfToken: z.string().length(64),
    flashMessage: z.string().nullable(),
  }),
]);

export const AuthValidationResponseSchema = z.object({
  message: z.string(),
  errors: z.array(
    z.object({
      field: z.string(),
      message: z.string(),
    }),
  ),
});

export const AuthSuccessResponseSchema = z.object({
  success: z.literal(true),
  message: z.string(),
  user: AuthenticatedUserSchema.optional(),
});

export const AuthErrorResponseSchema = z.object({
  message: z.string(),
});

export type LoginInput = z.infer<typeof LoginInputSchema>;
export type RegistrationInput = z.infer<typeof RegistrationInputSchema>;
export type PasswordRecoveryInput = z.infer<typeof PasswordRecoveryInputSchema>;
export type AuthenticatedUser = z.infer<typeof AuthenticatedUserSchema>;
export type CurrentUserResponse = z.infer<typeof CurrentUserResponseSchema>;
export type AuthValidationResponse = z.infer<typeof AuthValidationResponseSchema>;
export type AuthSuccessResponse = z.infer<typeof AuthSuccessResponseSchema>;
export type AuthErrorResponse = z.infer<typeof AuthErrorResponseSchema>;

export const QuestionExamTypeSchema = z.enum([
  "toefl",
  "ielts",
  "sat",
  "gre",
  "gmat",
  "dele",
  "delf",
  "testdaf",
  "jlpt",
  "hsk",
]);

export const QuestionDifficultySchema = z.enum(["facil", "medio", "dificil"]);

const RequiredQuestionTextSchema = z
  .string()
  .min(1)
  .refine((value) => value !== "0");

export const ImportedQuestionSchema = z.object({
  numero_questao: z.number().int().positive(),
  enunciado: RequiredQuestionTextSchema,
  alternativa_a: RequiredQuestionTextSchema,
  alternativa_b: RequiredQuestionTextSchema,
  alternativa_c: RequiredQuestionTextSchema,
  alternativa_d: RequiredQuestionTextSchema,
  alternativa_e: RequiredQuestionTextSchema,
  resposta_correta: RequiredQuestionTextSchema,
  dificuldade: QuestionDifficultySchema.default("medio"),
  materia: z.string().nullable().optional(),
  assunto: z.string().nullable().optional(),
  explicacao: z.string().nullable().optional(),
});

export const QuestionImportResponseSchema = z.object({
  success: z.literal(true),
  questoes_inseridas: z.number().int().nonnegative(),
  questoes_atualizadas: z.number().int().nonnegative(),
  total_processadas: z.number().int().nonnegative(),
  message: z.string(),
});

export const QuestionStatsItemSchema = z.object({
  tipo_prova: QuestionExamTypeSchema,
  total: z.number().int().nonnegative(),
  ultima_atualizacao: z.string().nullable(),
});

export const QuestionStatsResponseSchema = z.object({
  items: z.array(QuestionStatsItemSchema),
  total: z.number().int().nonnegative(),
});

export type QuestionExamType = z.infer<typeof QuestionExamTypeSchema>;
export type ImportedQuestion = z.infer<typeof ImportedQuestionSchema>;
export type QuestionImportResponse = z.infer<typeof QuestionImportResponseSchema>;
export type QuestionStatsResponse = z.infer<typeof QuestionStatsResponseSchema>;

export const SimulatorExamTypeSchema = z.enum([
  "toefl",
  "ielts",
  "sat",
  "gre",
  "dele",
  "delf",
  "testdaf",
  "jlpt",
  "hsk",
]);

export const SimulatorExamSchema = z.object({
  tipo_prova: SimulatorExamTypeSchema,
  nome: z.string(),
  descricao: z.string(),
  icone: z.string(),
  cor: z.string(),
  duracao: z.string(),
  questoes: z.number().int().positive(),
  duracao_minutos: z.number().int().positive(),
  questoes_total: z.number().int().positive(),
});

export const SimulatorDashboardResponseSchema = z.object({
  exams: z.array(SimulatorExamSchema),
  stats: z.object({
    total_testes: z.number().int().nonnegative(),
    media_pontuacao: z.number().nullable(),
  }),
});

export const SimulatorStartInputSchema = z.object({
  tipo_prova: SimulatorExamTypeSchema,
});

export const SimulatorStartResponseSchema = z.object({
  sessao_id: z.number().int().positive(),
  tipo_prova: SimulatorExamTypeSchema,
});

export const SimulatorQuestionSchema = z.object({
  id: z.number().int().positive(),
  numero: z.number().int().positive(),
  enunciado: z.string(),
  tipo_questao: z.enum(["multipla_escolha", "dissertativa"]),
  alternativas: z.record(z.string(), z.string()).optional(),
  dificuldade: z.string().nullable(),
  materia: z.string().nullable(),
  assunto: z.string().nullable(),
  explicacao: z.string().nullable(),
});

export const SimulatorSessionResponseSchema = z.object({
  sessao_id: z.number().int().positive(),
  tipo_prova: SimulatorExamTypeSchema,
  nome: z.string(),
  cor: z.string(),
  status: z.enum(["ativo", "expirado"]),
  tempo_restante: z.number().int().nonnegative(),
  questoes_total: z.number().int().positive(),
  questoes: z.array(SimulatorQuestionSchema),
});

export const SimulatorAnswerInputSchema = z.object({
  sessao_id: z.number().int().positive(),
  questao_numero: z.number().int().positive(),
  resposta: z
    .string()
    .min(1)
    .refine((value) => value !== "0"),
  tipo_resposta: z.enum(["multipla_escolha", "dissertativa"]).default("multipla_escolha"),
});

export const SimulatorAnswerResponseSchema = z.object({
  success: z.boolean(),
  esta_correta: z.boolean().optional(),
  tipo_resposta: z.enum(["multipla_escolha", "dissertativa"]).optional(),
  questao_numero: z.number().int().positive().optional(),
  message: z.string().optional(),
  error: z.string().optional(),
});

export const SimulatorFinishInputSchema = z.object({
  sessao_id: z.number().int().positive(),
  finalizar: z.literal("1"),
  respostas: z.record(z.string(), z.string()),
});

export const SimulatorFinishResponseSchema = z.object({
  sucesso: z.literal(true),
  pontuacao: z.number().nonnegative(),
  acertos: z.number().int().nonnegative(),
  questoes_respondidas: z.number().int().nonnegative(),
  tempo_gasto: z.number().int().nonnegative(),
});

export const SimulatorErrorResponseSchema = z.object({
  success: z.literal(false),
  error: z.string(),
});

export type SimulatorExamType = z.infer<typeof SimulatorExamTypeSchema>;
export type SimulatorExam = z.infer<typeof SimulatorExamSchema>;
export type SimulatorQuestion = z.infer<typeof SimulatorQuestionSchema>;
export type SimulatorDashboardResponse = z.infer<typeof SimulatorDashboardResponseSchema>;
export type SimulatorSessionResponse = z.infer<typeof SimulatorSessionResponseSchema>;
export type SimulatorFinishResponse = z.infer<typeof SimulatorFinishResponseSchema>;

export const TestResultResponseSchema = z.object({
  sessao_id: z.number().int().positive(),
  tipo_prova: z.string(),
  nome: z.string(),
  cor: z.string(),
  pontuacao: z.number(),
  acertos: z.number().int().nonnegative(),
  total_questoes: z.number().int().nonnegative(),
  questoes_respondidas: z.number().int().nonnegative(),
  tempo_gasto: z.number().int().nonnegative(),
});

export const TestReviewQuestionSchema = z.object({
  id: z.number().int().positive(),
  numero_questao: z.number().int().positive(),
  enunciado: z.string(),
  tipo_questao: z.string().nullable(),
  alternativas: z.record(z.string(), z.string()),
  resposta_correta: z.string().nullable(),
  resposta_dissertativa: z.string().nullable(),
  materia: z.string().nullable(),
  assunto: z.string().nullable(),
  resposta_usuario: z.string().nullable(),
  resposta_dissertativa_usuario: z.string().nullable(),
  esta_correta: z.boolean().nullable(),
});

export const TestReviewResponseSchema = z.object({
  session: z.object({
    sessao_id: z.number().int().positive(),
    tipo_prova: z.string(),
    nome: z.string(),
    cor: z.string(),
    inicio: z.string(),
    pontuacao: z.number(),
    acertos: z.number().int().nonnegative(),
    questoes_respondidas: z.number().int().nonnegative(),
    tempo_gasto: z.number().int().nonnegative(),
  }),
  questions: z.array(TestReviewQuestionSchema),
});

export const TestHistoryItemSchema = z.object({
  sessao_id: z.number().int().positive(),
  tipo_prova: z.string(),
  nome: z.string(),
  cor: z.string(),
  icone: z.string(),
  data_realizacao: z.string(),
  acertos: z.number().int().nonnegative(),
  total_questoes: z.number().int().nonnegative(),
  tempo_gasto: z.number().int().nonnegative(),
  pontuacao: z.number(),
});

export const TestHistoryResponseSchema = z.object({
  items: z.array(TestHistoryItemSchema),
  stats: z.object({
    total_testes: z.number().int().nonnegative(),
    media_geral: z.number(),
    melhor_pontuacao: z.number(),
    testes_aprovados: z.number().int().nonnegative(),
  }),
  by_type: z.array(
    z.object({
      tipo_prova: z.string(),
      nome: z.string(),
      cor: z.string(),
      icone: z.string(),
      media_pontuacao: z.number(),
      testes_por_tipo: z.number().int().nonnegative(),
    }),
  ),
});

export const SessionHistoryItemSchema = z.object({
  sessao_id: z.number().int().positive(),
  tipo_prova: z.string(),
  nome: z.string(),
  cor: z.string(),
  inicio: z.string(),
  fim: z.string().nullable(),
  status: z.string(),
  pontuacao_final: z.number(),
  acertos: z.number().int().nonnegative(),
  questoes_respondidas: z.number().int().nonnegative(),
  tempo_gasto: z.number().int().nonnegative(),
  duracao_minutos: z.number().int().nonnegative(),
  total_questoes: z.number().int().nonnegative(),
  pontuacao_calculada: z.number(),
});

export const SessionHistoryResponseSchema = z.object({
  items: z.array(SessionHistoryItemSchema),
  stats: z.object({
    total_provas: z.number().int().nonnegative(),
    questoes_respondidas: z.number().int().nonnegative(),
    acertos: z.number().int().nonnegative(),
    tempo_total: z.number().int().nonnegative(),
    media_pontuacao: z.number(),
  }),
});

export type TestResultResponse = z.infer<typeof TestResultResponseSchema>;
export type TestReviewResponse = z.infer<typeof TestReviewResponseSchema>;
export type TestHistoryResponse = z.infer<typeof TestHistoryResponseSchema>;
export type SessionHistoryResponse = z.infer<typeof SessionHistoryResponseSchema>;

const PhpTrimmedForumStringSchema = z.string().transform(trimPhpInput);
const RequiredForumStringSchema = PhpTrimmedForumStringSchema.refine((value) => !isPhpEmpty(value));

export const ForumCategorySchema = z.object({
  id: z.number().int().positive(),
  nome: z.string(),
  descricao: z.string().nullable(),
  cor: z.string(),
  icone: z.string(),
  ativo: z.boolean(),
});

export const ForumCategoryInputSchema = z.object({
  nome: RequiredForumStringSchema.pipe(z.string().max(100)),
  descricao: PhpTrimmedForumStringSchema,
  cor: z.string().regex(/^#[0-9a-fA-F]{6}$/),
  icone: z.string().max(50),
});

export const ForumTopicInputSchema = z.object({
  categoria_id: z.number().int().positive(),
  titulo: RequiredForumStringSchema.pipe(z.string().max(255)),
  conteudo: RequiredForumStringSchema,
});

export const ForumReplyInputSchema = z.object({
  topico_id: z.number().int().positive(),
  conteudo: RequiredForumStringSchema,
});

export const ForumLikeInputSchema = z.object({
  tipo: z.enum(["topico", "resposta"]),
  id: z.number().int().positive(),
});

export const ForumTopicActionSchema = z.enum(["aprovar", "rejeitar", "fixar", "fechar", "deletar"]);

export const ForumReplyActionSchema = z.enum(["aprovar", "rejeitar", "deletar"]);

export const ForumModerationInputSchema = z.object({
  tipo: z.enum(["topico", "resposta"]),
  id: z.number().int().positive(),
  acao: z.string().min(1),
  motivo: z.string().nullable().optional(),
});

export const ForumCategoryListResponseSchema = z.object({
  items: z.array(ForumCategorySchema),
});

export const ForumTopicSchema = z.object({
  id: z.number().int().positive(),
  categoria_id: z.number().int().positive(),
  titulo: z.string(),
  conteudo: z.string(),
  visualizacoes: z.number().int().nonnegative(),
  fixado: z.boolean(),
  fechado: z.boolean(),
  aprovado: z.boolean(),
  data_criacao: z.string(),
  data_atualizacao: z.string(),
  autor_id: z.number().int().positive(),
  autor_nome: z.string(),
  categoria_nome: z.string(),
  categoria_cor: z.string(),
  categoria_icone: z.string(),
  total_respostas: z.number().int().nonnegative(),
  total_likes: z.number().int().nonnegative(),
});

export const ForumReplySchema = z.object({
  id: z.number().int().positive(),
  topico_id: z.number().int().positive(),
  autor_id: z.number().int().positive(),
  autor_nome: z.string(),
  conteudo: z.string(),
  aprovado: z.boolean(),
  data_criacao: z.string(),
  total_likes: z.number().int().nonnegative(),
});

export const ForumTopicListResponseSchema = z.object({
  items: z.array(ForumTopicSchema),
});

export const ForumTopicDetailResponseSchema = z.object({
  topic: ForumTopicSchema,
  replies: z.array(ForumReplySchema),
});

export const ForumAdminStatsResponseSchema = z.object({
  total_topicos: z.number().int().nonnegative(),
  topicos_pendentes: z.number().int().nonnegative(),
  total_respostas: z.number().int().nonnegative(),
  respostas_pendentes: z.number().int().nonnegative(),
  total_usuarios: z.number().int().nonnegative(),
});

export const ForumAdminUserSchema = z.object({
  id: z.number().int().positive(),
  nome: z.string(),
  usuario: z.string(),
  email: z.string(),
  is_admin: z.boolean(),
  ativo: z.boolean(),
  data_criacao: z.string(),
  total_topicos: z.number().int().nonnegative(),
  total_respostas: z.number().int().nonnegative(),
});

export const ForumModerationLogSchema = z.object({
  id: z.number().int().positive(),
  moderador_nome: z.string(),
  tipo_conteudo: z.string().nullable(),
  conteudo_id: z.number().int().positive().nullable(),
  acao: z.string(),
  motivo: z.string().nullable(),
  data_acao: z.string(),
});

export const ForumAdminDashboardResponseSchema = z.object({
  stats: ForumAdminStatsResponseSchema,
  topics: z.array(ForumTopicSchema),
  pendingReplies: z.array(ForumReplySchema.extend({ topico_titulo: z.string() })),
  categories: z.array(ForumCategorySchema),
  users: z.array(ForumAdminUserSchema),
  logs: z.array(ForumModerationLogSchema),
});

export const ForumMutationResponseSchema = z.object({
  success: z.literal(true),
  message: z.string(),
});

export type ForumCategory = z.infer<typeof ForumCategorySchema>;
export type ForumTopic = z.infer<typeof ForumTopicSchema>;
export type ForumReply = z.infer<typeof ForumReplySchema>;
export type ForumAdminDashboardResponse = z.infer<typeof ForumAdminDashboardResponseSchema>;
export type ForumAdminUser = z.infer<typeof ForumAdminUserSchema>;
export type ForumModerationLog = z.infer<typeof ForumModerationLogSchema>;

export const CountryGuideSummarySchema = z.object({
  id: z.string(),
  name: z.string(),
  flag: z.string(),
  continent: ContinentSchema,
  description: z.string(),
  highlights: z.array(z.string()),
});

export const CountryGuideSchema = CountryGuideSummarySchema.extend({
  sections: z.array(
    z.object({
      title: z.string(),
      paragraphs: z.array(z.string()),
      links: z.array(z.object({ label: z.string(), url: z.url() })),
    }),
  ),
});

export const CountryGuideListResponseSchema = z.object({
  items: z.array(CountryGuideSummarySchema),
});

export const UniversityDetailSchema = z.object({
  title: z.string(),
  value: z.string(),
  description: z.string(),
});

export const UniversitySchema = z.object({
  id: z.string(),
  rank: z.number().int().positive(),
  name: z.string(),
  country: z.string(),
  countryCode: z.string(),
  region: z.string(),
  score: z.number().nonnegative(),
  logo: z.string(),
  location: z.string(),
  details: z.array(UniversityDetailSchema),
});

export const UniversityListResponseSchema = z.object({
  items: z.array(UniversitySchema),
  page: z.number().int().positive(),
  pageSize: z.number().int().positive(),
  total: z.number().int().nonnegative(),
});

export const CountryExplorationResponseSchema = z.object({
  explored: z.boolean(),
});

export const GpaGradeSchema = z.object({
  subject: z.string().trim().min(1).max(100),
  grade: z.number().min(0).max(10),
});

export const SaveGpaInputSchema = z.object({
  gpa: z.number().min(0).max(4),
  grades: z.array(GpaGradeSchema).min(1).max(30),
});

export const SaveGpaResponseSchema = z.object({
  success: z.literal(true),
  message: z.string(),
});

export const StudentProfileSchema = z.object({
  school: z.string().nullable(),
  grade: z.string().nullable(),
  location: z.string().nullable(),
  gpa: z.number().min(0).max(4).nullable(),
  languages: z.array(z.string()),
  exams: z.array(z.string()),
  interests: z.string().nullable(),
  bio: z.string().nullable(),
  countryTrackingEnabled: z.boolean(),
});

export const UpdateStudentProfileInputSchema = StudentProfileSchema.omit({
  gpa: true,
}).extend({
  school: z.string().trim().max(150).nullable(),
  grade: z.string().trim().max(50).nullable(),
  location: z.string().trim().max(150).nullable(),
  gpa: z.number().min(0).max(4).nullable(),
  languages: z.array(z.string().trim().min(1).max(50)).max(20),
  exams: z.array(z.string().trim().min(1).max(50)).max(20),
  interests: z.string().trim().max(255).nullable(),
  bio: z.string().trim().max(2000).nullable(),
});

export const StudentProfileResponseSchema = z.object({
  profile: StudentProfileSchema,
});

export const StudyMutationResponseSchema = z.object({
  success: z.literal(true),
  message: z.string(),
});

export const NotificationSchema = z.object({
  id: z.number().int().positive(),
  type: z.enum(["forum_reply", "forum_mention", "badge", "level", "system"]),
  title: z.string(),
  message: z.string(),
  link: z.string().nullable(),
  read: z.boolean(),
  createdAt: z.string(),
});

export const NotificationListResponseSchema = z.object({
  items: z.array(NotificationSchema),
  unreadCount: z.number().int().nonnegative(),
});

export const NotificationMutationResponseSchema = z.object({
  success: z.literal(true),
  message: z.string(),
});

export const BadgeSchema = z.object({
  code: z.string(),
  name: z.string(),
  description: z.string(),
  icon: z.string(),
  category: z.enum(["provas", "forum", "gpa", "paises", "outros"]),
  rarity: z.enum(["comum", "raro", "epico", "lendario"]),
  acquiredAt: z.string(),
  context: z.string().nullable(),
});

export const UserBadgesResponseSchema = z.object({
  items: z.array(BadgeSchema),
});

export type CountryGuideSummary = z.infer<typeof CountryGuideSummarySchema>;
export type CountryGuide = z.infer<typeof CountryGuideSchema>;
export type University = z.infer<typeof UniversitySchema>;
export type SaveGpaInput = z.infer<typeof SaveGpaInputSchema>;
export type StudentProfile = z.infer<typeof StudentProfileSchema>;
export type UpdateStudentProfileInput = z.infer<typeof UpdateStudentProfileInputSchema>;
export type Notification = z.infer<typeof NotificationSchema>;
export type Badge = z.infer<typeof BadgeSchema>;
