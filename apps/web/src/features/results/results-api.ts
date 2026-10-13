import {
  AuthErrorResponseSchema,
  SessionHistoryResponseSchema,
  TestHistoryResponseSchema,
  TestResultResponseSchema,
  TestReviewResponseSchema,
} from "@daydreaming/shared";

export class ResultsApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "ResultsApiError";
  }
}

async function checkedJson(response: Response): Promise<unknown> {
  if (!response.ok) {
    const parsedError = AuthErrorResponseSchema.safeParse(await response.json());
    throw new ResultsApiError(
      parsedError.success ? parsedError.data.message : "Não foi possível concluir a solicitação.",
    );
  }
  return response.json() as Promise<unknown>;
}

export async function getTestResult(sessionId: string) {
  const response = await fetch(`/api/results/${encodeURIComponent(sessionId)}`, {
    credentials: "same-origin",
  });
  return TestResultResponseSchema.parse(await checkedJson(response));
}

export async function getTestReview(sessionId: string) {
  const response = await fetch(`/api/results/${encodeURIComponent(sessionId)}/review`, {
    credentials: "same-origin",
  });
  return TestReviewResponseSchema.parse(await checkedJson(response));
}

export async function getTestHistory() {
  const response = await fetch("/api/results/history/tests", { credentials: "same-origin" });
  return TestHistoryResponseSchema.parse(await checkedJson(response));
}

export async function getSessionHistory() {
  const response = await fetch("/api/results/history/sessions", { credentials: "same-origin" });
  return SessionHistoryResponseSchema.parse(await checkedJson(response));
}
