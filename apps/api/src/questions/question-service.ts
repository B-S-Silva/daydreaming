import { ImportedQuestionSchema, QuestionExamTypeSchema } from "@daydreaming/shared";
import type { ImportedQuestion, QuestionExamType } from "@daydreaming/shared";
import type { QuestionRepository } from "./question-repository.js";

export class QuestionService {
  constructor(private readonly repository: QuestionRepository) {}

  private formatPhpDateTime(date: Date): string {
    const twoDigits = (value: number): string => String(value).padStart(2, "0");
    return `${date.getFullYear()}-${twoDigits(date.getMonth() + 1)}-${twoDigits(date.getDate())} ${twoDigits(date.getHours())}:${twoDigits(date.getMinutes())}:${twoDigits(date.getSeconds())}`;
  }

  async example(rawExamType: unknown): Promise<{ examType: QuestionExamType; json: string }> {
    const parsedType = QuestionExamTypeSchema.safeParse(rawExamType);
    if (!parsedType.success) {
      throw new QuestionImportValidationError("Tipo de prova inválido.");
    }

    const examType = parsedType.data;
    const example = {
      metadata: {
        tipo_prova: examType,
        versao: "1.0",
        data_criacao: this.formatPhpDateTime(new Date()),
        total_questoes: 2,
      },
      questoes: [
        {
          numero_questao: 1,
          enunciado: "Esta é uma questão de exemplo. Qual é a resposta correta?",
          alternativa_a: "Primeira alternativa",
          alternativa_b: "Segunda alternativa",
          alternativa_c: "Terceira alternativa",
          alternativa_d: "Quarta alternativa",
          alternativa_e: "Opção E",
          resposta_correta: "a",
          dificuldade: "medio",
          materia: "Exemplo",
          assunto: "Questão de exemplo",
          explicacao: "Esta é uma explicação da resposta correta.",
        },
        {
          numero_questao: 2,
          enunciado: "Segunda questão de exemplo para demonstrar a estrutura.",
          alternativa_a: "Opção A",
          alternativa_b: "Opção B",
          alternativa_c: "Opção C",
          alternativa_d: "Opção D",
          alternativa_e: "Opção E",
          resposta_correta: "b",
          dificuldade: "facil",
          materia: "Exemplo",
          assunto: "Estrutura de dados",
          explicacao: "Explicação detalhada da segunda questão.",
        },
      ],
    };

    return { examType, json: JSON.stringify(example, null, 2) };
  }

  async import(
    rawExamType: unknown,
    rawQuestions: unknown,
  ): Promise<{ inserted: number; updated: number; total: number }> {
    const parsedType = QuestionExamTypeSchema.safeParse(rawExamType);
    if (!parsedType.success) {
      throw new QuestionImportValidationError("Tipo de prova inválido.");
    }

    const questions = this.validateQuestions(rawQuestions);
    const result = await this.repository.importQuestions(parsedType.data, questions);
    return {
      ...result,
      total: result.inserted + result.updated,
    };
  }

  private validateQuestions(raw: unknown): ImportedQuestion[] {
    const source =
      typeof raw === "object" && raw !== null && "questoes" in raw && Array.isArray(raw.questoes)
        ? raw.questoes
        : raw;

    if (!Array.isArray(source)) {
      throw new QuestionImportValidationError("Estrutura deve conter um array de questões");
    }

    return source.map((entry: unknown, index: number) => {
      const parsed = ImportedQuestionSchema.safeParse(entry);
      if (parsed.success) {
        return parsed.data;
      }

      const issue = parsed.error.issues[0];
      const field = issue?.path[0];
      if (field !== undefined) {
        throw new QuestionImportValidationError(`Campo obrigatório ausente: ${String(field)}`);
      }
      throw new QuestionImportValidationError(`Questão inválida na posição ${index + 1}.`);
    });
  }
}

export class QuestionImportValidationError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "QuestionImportValidationError";
  }
}
