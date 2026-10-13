import type {
  SessionHistoryResponse,
  TestHistoryResponse,
  TestResultResponse,
  TestReviewResponse,
} from "@daydreaming/shared";
import type { ResultsRepository } from "./results-repository.js";

export class ResultsNotFoundError extends Error {
  readonly statusCode = 404;

  constructor() {
    super("Resultado não encontrado.");
    this.name = "ResultsNotFoundError";
  }
}

export class ResultsService {
  constructor(private readonly repository: ResultsRepository) {}

  async getResult(userId: number, rawSessionId: string): Promise<TestResultResponse> {
    const sessionId = this.parseSessionId(rawSessionId);
    const result = await this.repository.findResult(userId, sessionId);
    if (!result) {
      throw new ResultsNotFoundError();
    }
    return result;
  }

  async getReview(userId: number, rawSessionId: string): Promise<TestReviewResponse> {
    const sessionId = this.parseSessionId(rawSessionId);
    const review = await this.repository.findReview(userId, sessionId);
    if (!review) {
      throw new ResultsNotFoundError();
    }
    return review;
  }

  getTestHistory(userId: number): Promise<TestHistoryResponse> {
    return this.repository.getTestHistory(userId);
  }

  getSessionHistory(userId: number): Promise<SessionHistoryResponse> {
    return this.repository.getSessionHistory(userId);
  }

  private parseSessionId(rawSessionId: string): number {
    if (!/^[1-9]\d*$/.test(rawSessionId)) {
      throw new ResultsNotFoundError();
    }
    const sessionId = Number(rawSessionId);
    if (!Number.isSafeInteger(sessionId)) {
      throw new ResultsNotFoundError();
    }
    return sessionId;
  }
}
