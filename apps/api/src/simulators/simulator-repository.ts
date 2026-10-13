import { and, count, eq, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type { SimulatorExamType } from "@daydreaming/shared";
import type {
  SimulatorAnswerToSave,
  SimulatorQuestionRecord,
  SimulatorRepository,
  SimulatorSessionRecord,
} from "./simulator-service.js";
import * as schema from "../db/schema.js";

function toQuestionRecord(row: typeof schema.questoes.$inferSelect): SimulatorQuestionRecord {
  return {
    id: row.id,
    numero: row.numeroQuestao,
    enunciado: row.enunciado,
    tipoQuestao: row.tipoQuestao,
    alternativaA: row.alternativaA,
    alternativaB: row.alternativaB,
    alternativaC: row.alternativaC,
    alternativaD: row.alternativaD,
    alternativaE: row.alternativaE,
    respostaCorreta: row.respostaCorreta,
    respostaDissertativa: row.respostaDissertativa,
    dificuldade: row.dificuldade,
    materia: row.materia,
    assunto: row.assunto,
    explicacao: row.explicacao,
  };
}

async function upsertFinalAnswer(
  transaction: Parameters<Parameters<MySql2Database<typeof schema>["transaction"]>[0]>[0],
  sessionId: number,
  answer: SimulatorAnswerToSave,
): Promise<void> {
  const { question } = answer;
  const answerType = question.tipoQuestao === "dissertativa" ? "dissertativa" : "multiple";
  const values = {
    respostaUsuario: answerType === "multiple" ? answer.answer : null,
    respostaDissertativaUsuario: answerType === "dissertativa" ? answer.answer : null,
    estaCorreta: answer.isCorrect,
  };
  const [existing] = await transaction
    .select({ id: schema.respostasUsuario.id })
    .from(schema.respostasUsuario)
    .where(
      and(
        eq(schema.respostasUsuario.sessaoId, sessionId),
        eq(schema.respostasUsuario.questaoNumero, question.numero),
      ),
    )
    .limit(1);
  if (existing) {
    await transaction
      .update(schema.respostasUsuario)
      .set({ ...values, dataResposta: sql`CURRENT_TIMESTAMP` })
      .where(eq(schema.respostasUsuario.id, existing.id));
    return;
  }
  await transaction.insert(schema.respostasUsuario).values({
    sessaoId: sessionId,
    questaoId: question.id,
    questaoNumero: question.numero,
    respostaUsuario: values.respostaUsuario,
    respostaDissertativaUsuario: values.respostaDissertativaUsuario,
    respostaCorreta: question.respostaCorreta,
    estaCorreta: answer.isCorrect,
  });
}

export class MySqlSimulatorRepository implements SimulatorRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async getUserStats(
    userId: number,
  ): Promise<{ total_testes: number; media_pontuacao: number | null }> {
    const [row] = await this.db
      .select({
        total: count(),
        average: sql<string | number | null>`AVG(CASE
          WHEN ${schema.sessoesTeste.tipoProva} = 'sat' THEN (${schema.sessoesTeste.acertos} / 120) * 100
          WHEN ${schema.sessoesTeste.tipoProva} = 'toefl' THEN (${schema.sessoesTeste.acertos} / 100) * 100
          WHEN ${schema.sessoesTeste.tipoProva} = 'ielts' THEN (${schema.sessoesTeste.acertos} / 40) * 100
          WHEN ${schema.sessoesTeste.tipoProva} = 'gre' THEN (${schema.sessoesTeste.acertos} / 80) * 100
          ELSE (${schema.sessoesTeste.acertos} / 120) * 100
        END)`,
      })
      .from(schema.sessoesTeste)
      .where(
        and(
          eq(schema.sessoesTeste.usuarioId, userId),
          eq(schema.sessoesTeste.status, "finalizado"),
        ),
      );
    return {
      total_testes: Number(row?.total ?? 0),
      media_pontuacao:
        row?.average === null || row?.average === undefined ? null : Number(row.average),
    };
  }

  async createSession(
    userId: number,
    examType: SimulatorExamType,
    durationMinutes: number,
  ): Promise<number> {
    const [result] = await this.db.insert(schema.sessoesTeste).values({
      usuarioId: userId,
      tipoProva: examType,
      inicio: sql`CURRENT_TIMESTAMP`,
      duracaoMinutos: durationMinutes,
      status: "ativo",
    });
    return Number(result.insertId);
  }

  async findSession(userId: number, sessionId: number): Promise<SimulatorSessionRecord | null> {
    const [row] = await this.db
      .select({
        id: schema.sessoesTeste.id,
        tipoProva: schema.sessoesTeste.tipoProva,
        status: schema.sessoesTeste.status,
        duracaoMinutos: schema.sessoesTeste.duracaoMinutos,
        tempoDecorrido: sql<number>`GREATEST(0, TIMESTAMPDIFF(SECOND, ${schema.sessoesTeste.inicio}, CURRENT_TIMESTAMP))`,
      })
      .from(schema.sessoesTeste)
      .where(and(eq(schema.sessoesTeste.id, sessionId), eq(schema.sessoesTeste.usuarioId, userId)))
      .limit(1);
    if (!row) {
      return null;
    }
    return {
      ...row,
      tipoProva: row.tipoProva as SimulatorExamType,
      status: row.status,
      duracaoMinutos: Number(row.duracaoMinutos),
      tempoDecorrido: Number(row.tempoDecorrido),
    };
  }

  async listQuestions(
    examType: SimulatorExamType,
    limit: number | null,
  ): Promise<SimulatorQuestionRecord[]> {
    const query = this.db
      .select()
      .from(schema.questoes)
      .where(
        and(
          eq(schema.questoes.tipoProva, examType),
          limit === null ? undefined : eq(schema.questoes.ativa, true),
        ),
      )
      .orderBy(schema.questoes.numeroQuestao);
    const rows =
      limit === null
        ? await this.db
            .select()
            .from(schema.questoes)
            .where(eq(schema.questoes.tipoProva, examType))
            .orderBy(schema.questoes.numeroQuestao)
        : await query.limit(limit);
    return rows.map(toQuestionRecord);
  }

  async findQuestion(
    examType: SimulatorExamType,
    questionNumber: number,
  ): Promise<SimulatorQuestionRecord | null> {
    const [row] = await this.db
      .select()
      .from(schema.questoes)
      .where(
        and(
          eq(schema.questoes.tipoProva, examType),
          eq(schema.questoes.numeroQuestao, questionNumber),
        ),
      )
      .limit(1);
    return row ? toQuestionRecord(row) : null;
  }

  async saveAnswer(
    userId: number,
    sessionId: number,
    question: SimulatorQuestionRecord,
    answer: string,
    answerType: "multipla_escolha" | "dissertativa",
    isCorrect: boolean,
    ip: string,
  ): Promise<void> {
    await this.db.transaction(async (transaction) => {
      const [existing] = await transaction
        .select({ id: schema.respostasUsuario.id })
        .from(schema.respostasUsuario)
        .where(
          and(
            eq(schema.respostasUsuario.sessaoId, sessionId),
            eq(schema.respostasUsuario.questaoNumero, question.numero),
          ),
        )
        .limit(1);
      const answerFields = {
        respostaUsuario: answerType === "dissertativa" ? null : answer,
        respostaDissertativaUsuario: answerType === "dissertativa" ? answer : null,
        estaCorreta: isCorrect,
      };
      if (existing) {
        await transaction
          .update(schema.respostasUsuario)
          .set({ ...answerFields, dataResposta: sql`CURRENT_TIMESTAMP` })
          .where(eq(schema.respostasUsuario.id, existing.id));
      } else {
        await transaction.insert(schema.respostasUsuario).values({
          sessaoId: sessionId,
          questaoId: question.id,
          questaoNumero: question.numero,
          ...answerFields,
          respostaCorreta: question.respostaCorreta,
        });
      }
      const [answered] = await transaction
        .select({ total: count() })
        .from(schema.respostasUsuario)
        .where(
          and(
            eq(schema.respostasUsuario.sessaoId, sessionId),
            sql`(${schema.respostasUsuario.respostaUsuario} IS NOT NULL OR ${schema.respostasUsuario.respostaDissertativaUsuario} IS NOT NULL)`,
          ),
        );
      await transaction
        .update(schema.sessoesTeste)
        .set({ questoesRespondidas: Number(answered?.total ?? 0) })
        .where(eq(schema.sessoesTeste.id, sessionId));
      await transaction.insert(schema.logsSistema).values({
        usuarioId: userId,
        acao: "resposta_salva",
        detalhes: JSON.stringify({
          questao_numero: question.numero,
          tipo_resposta: answerType,
          esta_correta: isCorrect,
          resposta_length: Buffer.byteLength(answer),
        }),
        dataHora: sql`CURRENT_TIMESTAMP`,
        ip,
      });
    });
  }

  async expireSession(userId: number, sessionId: number): Promise<void> {
    await this.db
      .update(schema.sessoesTeste)
      .set({ status: "expirado", fim: sql`CURRENT_TIMESTAMP` })
      .where(
        and(
          eq(schema.sessoesTeste.id, sessionId),
          eq(schema.sessoesTeste.usuarioId, userId),
          eq(schema.sessoesTeste.status, "ativo"),
        ),
      );
  }

  async finalizeSession(
    userId: number,
    sessionId: number,
    answers: SimulatorAnswerToSave[],
    result: { score: number; correct: number; answered: number },
  ): Promise<number | null> {
    return this.db.transaction(async (transaction) => {
      const [session] = await transaction
        .select({
          id: schema.sessoesTeste.id,
          tipoProva: schema.sessoesTeste.tipoProva,
          elapsed: sql<number>`GREATEST(0, TIMESTAMPDIFF(SECOND, ${schema.sessoesTeste.inicio}, CURRENT_TIMESTAMP))`,
        })
        .from(schema.sessoesTeste)
        .where(
          and(
            eq(schema.sessoesTeste.id, sessionId),
            eq(schema.sessoesTeste.usuarioId, userId),
            eq(schema.sessoesTeste.status, "ativo"),
          ),
        )
        .for("update")
        .limit(1);
      if (!session) {
        return null;
      }
      for (const answer of answers) {
        await upsertFinalAnswer(transaction, sessionId, answer);
      }
      const elapsed = Number(session.elapsed);
      await transaction
        .update(schema.sessoesTeste)
        .set({
          status: "finalizado",
          fim: sql`CURRENT_TIMESTAMP`,
          pontuacaoFinal: String(result.score),
          acertos: result.correct,
          questoesRespondidas: result.answered,
          tempoGasto: elapsed,
        })
        .where(eq(schema.sessoesTeste.id, sessionId));
      await transaction.insert(schema.resultadosTestes).values({
        usuarioId: userId,
        sessaoId: sessionId,
        tipoProva: session.tipoProva as SimulatorExamType,
        pontuacao: String(result.score),
        acertos: result.correct,
        totalQuestoes: 120,
        questoesRespondidas: result.answered,
        tempoGasto: elapsed,
      });
      return elapsed;
    });
  }
}
