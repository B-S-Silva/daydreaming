import {
  AuthErrorResponseSchema,
  QuestionImportResponseSchema,
  QuestionStatsResponseSchema,
} from "@daydreaming/shared";
import type { QuestionExamType } from "@daydreaming/shared";
import { getCurrentUser } from "../auth/auth-api.js";

export class QuestionsApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "QuestionsApiError";
  }
}

async function errorMessage(response: Response): Promise<string> {
  const parsed = AuthErrorResponseSchema.safeParse(await response.json());
  return parsed.success ? parsed.data.message : "Não foi possível concluir a solicitação.";
}

export async function getQuestionStats() {
  const response = await fetch("/api/admin/questions/stats", { credentials: "same-origin" });
  if (!response.ok) {
    throw new QuestionsApiError(await errorMessage(response));
  }
  return QuestionStatsResponseSchema.parse(await response.json());
}

export async function downloadQuestionExample(examType: QuestionExamType): Promise<void> {
  const response = await fetch(
    `/api/admin/questions/example?tipo_prova=${encodeURIComponent(examType)}`,
    { credentials: "same-origin" },
  );
  if (!response.ok) {
    throw new QuestionsApiError(await errorMessage(response));
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = `exemplo_questoes_${examType}.json`;
  anchor.click();
  URL.revokeObjectURL(url);
}

export async function importQuestions(input: { examType: QuestionExamType; file: File }) {
  const currentUser = await getCurrentUser();
  const data = new FormData();
  data.append("tipo_prova", input.examType);
  data.append("arquivo_questoes", input.file);

  const response = await fetch("/api/admin/questions/import", {
    method: "POST",
    credentials: "same-origin",
    headers: { "x-csrf-token": currentUser.csrfToken },
    body: data,
  });
  if (!response.ok) {
    throw new QuestionsApiError(await errorMessage(response));
  }
  return QuestionImportResponseSchema.parse(await response.json());
}
