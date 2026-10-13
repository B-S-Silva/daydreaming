import { and, count, desc, eq, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type { ImportedQuestion, QuestionExamType } from "@daydreaming/shared";
import * as schema from "../db/schema.js";

export interface QuestionStatsItem {
  tipo_prova: QuestionExamType;
  total: number;
  ultima_atualizacao: string | null;
}

export interface QuestionRepository {
  listStats(): Promise<QuestionStatsItem[]>;
  importQuestions(
    examType: QuestionExamType,
    questions: ImportedQuestion[],
  ): Promise<{ inserted: number; updated: number }>;
}

export class MySqlQuestionRepository implements QuestionRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async listStats(): Promise<QuestionStatsItem[]> {
    const rows = await this.db
      .select({
        examType: schema.questoes.tipoProva,
        total: count(),
        latest: sql<string | null>`MAX(${schema.questoes.dataCriacao})`,
      })
      .from(schema.questoes)
      .where(eq(schema.questoes.ativa, true))
      .groupBy(schema.questoes.tipoProva)
      .orderBy(desc(sql`MAX(${schema.questoes.dataCriacao})`));

    return rows.map((row) => ({
      tipo_prova: row.examType as QuestionExamType,
      total: Number(row.total),
      ultima_atualizacao: row.latest ?? null,
    }));
  }

  async importQuestions(
    examType: QuestionExamType,
    questions: ImportedQuestion[],
  ): Promise<{ inserted: number; updated: number }> {
    return this.db.transaction(async (transaction) => {
      let inserted = 0;
      let updated = 0;

      for (const question of questions) {
        const [existing] = await transaction
          .select({ id: schema.questoes.id })
          .from(schema.questoes)
          .where(
            and(
              eq(schema.questoes.tipoProva, examType),
              eq(schema.questoes.numeroQuestao, question.numero_questao),
            ),
          )
          .limit(1);

        const values = {
          enunciado: question.enunciado,
          alternativaA: question.alternativa_a,
          alternativaB: question.alternativa_b,
          alternativaC: question.alternativa_c,
          alternativaD: question.alternativa_d,
          alternativaE: question.alternativa_e,
          respostaCorreta: question.resposta_correta,
          dificuldade: question.dificuldade,
          materia: question.materia ?? null,
          assunto: question.assunto ?? null,
          explicacao: question.explicacao ?? null,
        };

        if (existing) {
          await transaction
            .update(schema.questoes)
            .set({ ...values, updatedAt: sql`CURRENT_TIMESTAMP` })
            .where(eq(schema.questoes.id, existing.id));
          updated += 1;
        } else {
          await transaction.insert(schema.questoes).values({
            ...values,
            tipoProva: examType,
            numeroQuestao: question.numero_questao,
          });
          inserted += 1;
        }
      }

      return { inserted, updated };
    });
  }
}
