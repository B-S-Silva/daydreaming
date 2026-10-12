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
