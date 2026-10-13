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
