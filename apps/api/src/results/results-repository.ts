import { and, count, desc, eq, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type {
  SessionHistoryResponse,
  TestHistoryResponse,
  TestResultResponse,
  TestReviewResponse,
} from "@daydreaming/shared";
import { getResultExamDisplay, getSimulatorExamDisplay } from "../simulators/simulator-service.js";
import * as schema from "../db/schema.js";

export interface ResultsRepository {
  findResult(userId: number, sessionId: number): Promise<TestResultResponse | null>;
  findReview(userId: number, sessionId: number): Promise<TestReviewResponse | null>;
  getTestHistory(userId: number): Promise<TestHistoryResponse>;
  getSessionHistory(userId: number): Promise<SessionHistoryResponse>;
}

function numeric(value: number | string | null | undefined): number {
  return value === null || value === undefined ? 0 : Number(value);
}

function dateString(value: Date): string {
  return value.toISOString();
}

function isPhpEmpty(value: string | null): boolean {
  return value === null || value === "" || value === "0";
}

function getExamTotalQuestions(examType: string): number {
  const totals: Record<string, number> = { sat: 120, toefl: 100, ielts: 40, gre: 80 };
  return totals[examType] ?? 120;
}

export class MySqlResultsRepository implements ResultsRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async findResult(userId: number, sessionId: number): Promise<TestResultResponse | null> {
    const [row] = await this.db
      .select({
        sessionId: schema.sessoesTeste.id,
        examType: schema.sessoesTeste.tipoProva,
        sessionScore: schema.sessoesTeste.pontuacaoFinal,
        resultScore: schema.resultadosTestes.pontuacao,
        resultCorrect: schema.resultadosTestes.acertos,
        totalQuestions: schema.resultadosTestes.totalQuestoes,
        answered: schema.resultadosTestes.questoesRespondidas,
        elapsed: schema.resultadosTestes.tempoGasto,
      })
      .from(schema.sessoesTeste)
      .leftJoin(
        schema.resultadosTestes,
        eq(schema.sessoesTeste.id, schema.resultadosTestes.sessaoId),
      )
      .where(and(eq(schema.sessoesTeste.id, sessionId), eq(schema.sessoesTeste.usuarioId, userId)))
      .limit(1);

    if (!row) {
      return null;
    }
    const display = getResultExamDisplay(row.examType);
    return {
      sessao_id: row.sessionId,
      tipo_prova: row.examType,
      ...display,
      pontuacao: numeric(row.resultScore ?? row.sessionScore),
      acertos: numeric(row.resultCorrect),
      total_questoes: numeric(row.totalQuestions),
      questoes_respondidas: numeric(row.answered),
      tempo_gasto: numeric(row.elapsed),
    };
  }

  async findReview(userId: number, sessionId: number): Promise<TestReviewResponse | null> {
    const [session] = await this.db
      .select({
        id: schema.sessoesTeste.id,
        examType: schema.sessoesTeste.tipoProva,
        startedAt: schema.sessoesTeste.inicio,
        score: schema.sessoesTeste.pontuacaoFinal,
        correct: schema.sessoesTeste.acertos,
        answered: schema.sessoesTeste.questoesRespondidas,
        elapsed: schema.sessoesTeste.tempoGasto,
      })
      .from(schema.sessoesTeste)
      .where(and(eq(schema.sessoesTeste.id, sessionId), eq(schema.sessoesTeste.usuarioId, userId)))
      .limit(1);
    if (!session) {
      return null;
    }

    const rows = await this.db
      .select({
        id: schema.questoes.id,
        number: schema.questoes.numeroQuestao,
        prompt: schema.questoes.enunciado,
        type: schema.questoes.tipoQuestao,
        alternativeA: schema.questoes.alternativaA,
        alternativeB: schema.questoes.alternativaB,
        alternativeC: schema.questoes.alternativaC,
        alternativeD: schema.questoes.alternativaD,
        alternativeE: schema.questoes.alternativaE,
        correctAnswer: schema.questoes.respostaCorreta,
        expectedEssayAnswer: schema.questoes.respostaDissertativa,
        subject: schema.questoes.materia,
        topic: schema.questoes.assunto,
        userAnswer: schema.respostasUsuario.respostaUsuario,
        userEssayAnswer: schema.respostasUsuario.respostaDissertativaUsuario,
        isCorrect: schema.respostasUsuario.estaCorreta,
      })
      .from(schema.questoes)
      .leftJoin(
        schema.respostasUsuario,
        and(
          eq(schema.questoes.id, schema.respostasUsuario.questaoId),
          eq(schema.respostasUsuario.sessaoId, sessionId),
        ),
      )
      .where(eq(schema.questoes.tipoProva, session.examType))
      .orderBy(schema.questoes.numeroQuestao);

    const display = getResultExamDisplay(session.examType);
    return {
      session: {
        sessao_id: session.id,
        tipo_prova: session.examType,
        ...display,
        inicio: dateString(session.startedAt),
        pontuacao: numeric(session.score),
        acertos: numeric(session.correct),
        questoes_respondidas: numeric(session.answered),
        tempo_gasto: numeric(session.elapsed),
      },
      questions: rows.map((row) => {
        const alternatives = [
          ["a", row.alternativeA],
          ["b", row.alternativeB],
          ["c", row.alternativeC],
          ["d", row.alternativeD],
          ["e", row.alternativeE],
        ].reduce<Record<string, string>>((result, [letter, value]) => {
          if (typeof letter === "string" && typeof value === "string" && !isPhpEmpty(value)) {
            result[letter] = value;
          }
          return result;
        }, {});
        return {
          id: row.id,
          numero_questao: row.number,
          enunciado: row.prompt,
          tipo_questao: row.type,
          alternativas: alternatives,
          resposta_correta: row.correctAnswer,
          resposta_dissertativa: row.expectedEssayAnswer,
          materia: row.subject,
          assunto: row.topic,
          resposta_usuario: row.userAnswer,
          resposta_dissertativa_usuario: row.userEssayAnswer,
          esta_correta: row.isCorrect,
        };
      }),
    };
  }

  async getTestHistory(userId: number): Promise<TestHistoryResponse> {
    const [historyRows, typeRows, [stats]] = await Promise.all([
      this.db
        .select({
          sessionId: schema.resultadosTestes.sessaoId,
          examType: schema.resultadosTestes.tipoProva,
          date: schema.resultadosTestes.dataRealizacao,
          correct: schema.resultadosTestes.acertos,
          totalQuestions: schema.resultadosTestes.totalQuestoes,
          elapsed: schema.resultadosTestes.tempoGasto,
          score: schema.resultadosTestes.pontuacao,
        })
        .from(schema.resultadosTestes)
        .innerJoin(
          schema.sessoesTeste,
          eq(schema.resultadosTestes.sessaoId, schema.sessoesTeste.id),
        )
        .where(eq(schema.resultadosTestes.usuarioId, userId))
        .orderBy(desc(schema.resultadosTestes.dataRealizacao))
        .limit(50),
      this.db
        .select({
          examType: schema.resultadosTestes.tipoProva,
          average: sql<string | number | null>`AVG(${schema.resultadosTestes.pontuacao})`,
          tests: count(),
        })
        .from(schema.resultadosTestes)
        .where(eq(schema.resultadosTestes.usuarioId, userId))
        .groupBy(schema.resultadosTestes.tipoProva),
      this.db
        .select({
          total: count(),
          average: sql<string | number | null>`AVG(${schema.resultadosTestes.pontuacao})`,
          best: sql<string | number | null>`MAX(${schema.resultadosTestes.pontuacao})`,
          passed: sql<
            string | number | null
          >`SUM(CASE WHEN ${schema.resultadosTestes.pontuacao} >= 70 THEN 1 ELSE 0 END)`,
        })
        .from(schema.resultadosTestes)
        .where(eq(schema.resultadosTestes.usuarioId, userId)),
    ]);

    return {
      items: historyRows.map((row) => ({
        sessao_id: row.sessionId,
        tipo_prova: row.examType,
        ...getSimulatorExamDisplay(row.examType),
        data_realizacao: dateString(row.date),
        acertos: numeric(row.correct),
        total_questoes: numeric(row.totalQuestions),
        tempo_gasto: numeric(row.elapsed),
        pontuacao: numeric(row.score),
      })),
      stats: {
        total_testes: numeric(stats?.total),
        media_geral: numeric(stats?.average),
        melhor_pontuacao: numeric(stats?.best),
        testes_aprovados: numeric(stats?.passed),
      },
      by_type: typeRows.map((row) => ({
        tipo_prova: row.examType,
        ...getSimulatorExamDisplay(row.examType),
        media_pontuacao: numeric(row.average),
        testes_por_tipo: numeric(row.tests),
      })),
    };
  }

  async getSessionHistory(userId: number): Promise<SessionHistoryResponse> {
    const rows = await this.db
      .select({
        sessionId: schema.sessoesTeste.id,
        examType: schema.sessoesTeste.tipoProva,
        startedAt: schema.sessoesTeste.inicio,
        endedAt: schema.sessoesTeste.fim,
        status: schema.sessoesTeste.status,
        score: schema.sessoesTeste.pontuacaoFinal,
        correct: schema.sessoesTeste.acertos,
        answered: schema.sessoesTeste.questoesRespondidas,
        elapsed: schema.sessoesTeste.tempoGasto,
        durationMinutes: schema.sessoesTeste.duracaoMinutos,
        totalQuestions: schema.resultadosTestes.totalQuestoes,
      })
      .from(schema.sessoesTeste)
      .leftJoin(
        schema.resultadosTestes,
        eq(schema.sessoesTeste.id, schema.resultadosTestes.sessaoId),
      )
      .where(
        and(
          eq(schema.sessoesTeste.usuarioId, userId),
          eq(schema.sessoesTeste.status, "finalizado"),
        ),
      )
      .orderBy(desc(schema.sessoesTeste.inicio));

    const items = rows.map((row) => {
      const denominator = getExamTotalQuestions(row.examType);
      const correct = numeric(row.correct);
      return {
        sessao_id: row.sessionId,
        tipo_prova: row.examType,
        ...getResultExamDisplay(row.examType),
        inicio: dateString(row.startedAt),
        fim: row.endedAt ? dateString(row.endedAt) : null,
        status: row.status,
        pontuacao_final: numeric(row.score),
        acertos: correct,
        questoes_respondidas: numeric(row.answered),
        tempo_gasto: numeric(row.elapsed),
        duracao_minutos: numeric(row.durationMinutes),
        total_questoes: numeric(row.totalQuestions),
        pontuacao_calculada: (correct / denominator) * 100,
      };
    });

    return {
      items,
      stats: {
        total_provas: items.length,
        questoes_respondidas: items.reduce((total, item) => total + item.questoes_respondidas, 0),
        acertos: items.reduce((total, item) => total + item.acertos, 0),
        tempo_total: items.reduce((total, item) => total + item.tempo_gasto, 0),
        media_pontuacao:
          items.length === 0
            ? 0
            : items.reduce((total, item) => total + item.pontuacao_calculada, 0) / items.length,
      },
    };
  }
}
