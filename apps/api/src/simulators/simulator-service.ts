import {
  SimulatorAnswerInputSchema,
  SimulatorExamTypeSchema,
  SimulatorFinishInputSchema,
  SimulatorStartInputSchema,
} from "@daydreaming/shared";
import type { SimulatorExam, SimulatorExamType, SimulatorQuestion } from "@daydreaming/shared";

export interface SimulatorQuestionRecord {
  id: number;
  numero: number;
  enunciado: string;
  tipoQuestao: string | null;
  alternativaA: string | null;
  alternativaB: string | null;
  alternativaC: string | null;
  alternativaD: string | null;
  alternativaE: string | null;
  respostaCorreta: string | null;
  respostaDissertativa: string | null;
  dificuldade: string | null;
  materia: string | null;
  assunto: string | null;
  explicacao: string | null;
}

export interface SimulatorSessionRecord {
  id: number;
  tipoProva: SimulatorExamType;
  status: "ativo" | "finalizado" | "expirado";
  duracaoMinutos: number;
  tempoDecorrido: number;
}

export interface SimulatorAnswerToSave {
  question: SimulatorQuestionRecord;
  answer: string;
  isCorrect: boolean;
}

export interface SimulatorRepository {
  getUserStats(userId: number): Promise<{ total_testes: number; media_pontuacao: number | null }>;
  createSession(
    userId: number,
    examType: SimulatorExamType,
    durationMinutes: number,
  ): Promise<number>;
  findSession(userId: number, sessionId: number): Promise<SimulatorSessionRecord | null>;
  listQuestions(
    examType: SimulatorExamType,
    limit: number | null,
  ): Promise<SimulatorQuestionRecord[]>;
  findQuestion(
    examType: SimulatorExamType,
    questionNumber: number,
  ): Promise<SimulatorQuestionRecord | null>;
  saveAnswer(
    userId: number,
    sessionId: number,
    question: SimulatorQuestionRecord,
    answer: string,
    answerType: "multipla_escolha" | "dissertativa",
    isCorrect: boolean,
    ip: string,
  ): Promise<void>;
  expireSession(userId: number, sessionId: number): Promise<void>;
  finalizeSession(
    userId: number,
    sessionId: number,
    answers: SimulatorAnswerToSave[],
    result: { score: number; correct: number; answered: number },
  ): Promise<number | null>;
}

export class SimulatorValidationError extends Error {
  constructor(
    message: string,
    readonly statusCode = 400,
  ) {
    super(message);
    this.name = "SimulatorValidationError";
  }
}

const exams = [
  {
    tipo_prova: "toefl",
    nome: "TOEFL",
    descricao: "Test of English as a Foreign Language",
    icone: "🇺🇸",
    cor: "#4CAF50",
    duracao: "3h00min",
    questoes: 100,
    duracao_minutos: 180,
    questoes_total: 100,
  },
  {
    tipo_prova: "ielts",
    nome: "IELTS",
    descricao: "International English Language Testing System",
    icone: "🇬🇧",
    cor: "#2196F3",
    duracao: "2h45min",
    questoes: 40,
    duracao_minutos: 165,
    questoes_total: 40,
  },
  {
    tipo_prova: "sat",
    nome: "SAT",
    descricao: "Scholastic Assessment Test",
    icone: "🎓",
    cor: "#FF9800",
    duracao: "3h00min",
    questoes: 120,
    duracao_minutos: 180,
    questoes_total: 120,
  },
  {
    tipo_prova: "gre",
    nome: "GRE",
    descricao: "Graduate Record Examinations",
    icone: "🎯",
    cor: "#9C27B0",
    duracao: "3h45min",
    questoes: 80,
    duracao_minutos: 225,
    questoes_total: 80,
  },
  {
    tipo_prova: "dele",
    nome: "DELE",
    descricao: "Diplomas de Español como Lengua Extranjera",
    icone: "🇪🇸",
    cor: "#E91E63",
    duracao: "4h00min",
    questoes: 50,
    duracao_minutos: 240,
    questoes_total: 80,
  },
  {
    tipo_prova: "delf",
    nome: "DELF",
    descricao: "Diplôme d'Études en Langue Française",
    icone: "🇫🇷",
    cor: "#3F51B5",
    duracao: "2h30min",
    questoes: 45,
    duracao_minutos: 150,
    questoes_total: 75,
  },
  {
    tipo_prova: "testdaf",
    nome: "TestDaF",
    descricao: "Test Deutsch als Fremdsprache",
    icone: "🇩🇪",
    cor: "#795548",
    duracao: "3h10min",
    questoes: 35,
    duracao_minutos: 190,
    questoes_total: 85,
  },
  {
    tipo_prova: "jlpt",
    nome: "JLPT",
    descricao: "Japanese Language Proficiency Test",
    icone: "🇯🇵",
    cor: "#9C27B0",
    duracao: "2h50min",
    questoes: 60,
    duracao_minutos: 170,
    questoes_total: 95,
  },
  {
    tipo_prova: "hsk",
    nome: "HSK",
    descricao: "Hanyu Shuiping Kaoshi (Chinese Proficiency Test)",
    icone: "🇨🇳",
    cor: "#F44336",
    duracao: "2h30min",
    questoes: 100,
    duracao_minutos: 135,
    questoes_total: 70,
  },
] satisfies SimulatorExam[];

function findExam(rawType: unknown): SimulatorExam {
  const parsed = SimulatorExamTypeSchema.safeParse(rawType);
  const exam = parsed.success ? exams.find((item) => item.tipo_prova === parsed.data) : undefined;
  if (!exam) {
    throw new SimulatorValidationError("Tipo de prova inválido.");
  }
  return exam;
}

function phpTrim(value: string): string {
  const isTrimCharacter = (character: string): boolean =>
    [0, 9, 10, 11, 13, 32].includes(character.charCodeAt(0));
  let start = 0;
  let end = value.length;
  while (start < end && isTrimCharacter(value[start]!)) {
    start += 1;
  }
  while (end > start && isTrimCharacter(value[end - 1]!)) {
    end -= 1;
  }
  return value.slice(start, end);
}

function phpEmpty(value: string | null): boolean {
  return value === null || value === "" || value === "0";
}

function phpLower(value: string): string {
  return value.replace(/[A-Z]/g, (character) => String.fromCharCode(character.charCodeAt(0) + 32));
}

function questionExpectedAnswer(question: SimulatorQuestionRecord): string {
  if (question.tipoQuestao === "dissertativa" && !phpEmpty(question.respostaDissertativa)) {
    return question.respostaDissertativa!;
  }
  return question.respostaCorreta ?? "";
}

function isCorrectIncremental(question: SimulatorQuestionRecord, answer: string): boolean {
  if (question.tipoQuestao === "dissertativa") {
    const normalizedAnswer = phpLower(phpTrim(answer));
    const normalizedExpected = phpLower(phpTrim(questionExpectedAnswer(question)));
    return normalizedAnswer === normalizedExpected || normalizedAnswer.includes(normalizedExpected);
  }
  return phpLower(answer) === phpLower(question.respostaCorreta ?? "");
}

function publicQuestion(question: SimulatorQuestionRecord): SimulatorQuestion {
  const tipoQuestao = question.tipoQuestao === "dissertativa" ? "dissertativa" : "multipla_escolha";
  const alternatives: Record<string, string> = {};
  const answerChoices: Array<[string, string | null]> = [
    ["a", question.alternativaA],
    ["b", question.alternativaB],
    ["c", question.alternativaC],
    ["d", question.alternativaD],
    ["e", question.alternativaE],
  ];
  for (const [letter, value] of answerChoices) {
    if (value !== null && !phpEmpty(value)) {
      alternatives[letter] = value;
    }
  }

  return {
    id: question.id,
    numero: question.numero,
    enunciado: question.enunciado,
    tipo_questao: tipoQuestao,
    ...(tipoQuestao === "multipla_escolha" ? { alternativas: alternatives } : {}),
    dificuldade: question.dificuldade,
    materia: question.materia,
    assunto: question.assunto,
    explicacao: question.explicacao,
  };
}

export class SimulatorService {
  constructor(private readonly repository: SimulatorRepository) {}

  async dashboard(userId: number) {
    return { exams, stats: await this.repository.getUserStats(userId) };
  }

  async start(
    userId: number,
    rawInput: unknown,
  ): Promise<{ sessionId: number; exam: SimulatorExam }> {
    const parsed = SimulatorStartInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new SimulatorValidationError("Tipo de prova inválido.");
    }
    const exam = findExam(parsed.data.tipo_prova);
    const sessionId = await this.repository.createSession(
      userId,
      exam.tipo_prova,
      exam.duracao_minutos,
    );
    return { sessionId, exam };
  }

  async loadSession(userId: number, rawSessionId: unknown) {
    const sessionId = this.parseSessionId(rawSessionId);
    const session = await this.repository.findSession(userId, sessionId);
    if (!session) {
      throw new SimulatorValidationError("Sessão não encontrada ou não autorizada.", 404);
    }
    if (session.status === "finalizado") {
      throw new SimulatorValidationError("Sessão não está ativa.", 409);
    }
    const exam = findExam(session.tipoProva);
    const isExpired =
      session.status === "expirado" || session.tempoDecorrido >= session.duracaoMinutos * 60;
    if (isExpired && session.status !== "expirado") {
      await this.repository.expireSession(userId, sessionId);
    }
    const questions = isExpired
      ? []
      : (await this.repository.listQuestions(exam.tipo_prova, exam.questoes_total)).map(
          publicQuestion,
        );
    return {
      sessao_id: session.id,
      tipo_prova: exam.tipo_prova,
      nome: exam.nome,
      cor: exam.cor,
      status: isExpired ? ("expirado" as const) : ("ativo" as const),
      tempo_restante: isExpired
        ? 0
        : Math.max(0, session.duracaoMinutos * 60 - session.tempoDecorrido),
      questoes_total: exam.questoes_total,
      questoes: questions,
    };
  }

  async saveAnswer(userId: number, ip: string, rawInput: unknown) {
    const parsed = SimulatorAnswerInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new SimulatorValidationError("Dados obrigatórios não fornecidos");
    }
    const input = parsed.data;
    const session = await this.repository.findSession(userId, input.sessao_id);
    if (!session) {
      throw new SimulatorValidationError("Sessão não encontrada ou não autorizada", 404);
    }
    if (session.status !== "ativo") {
      throw new SimulatorValidationError("Sessão não está ativa", 409);
    }
    const question = await this.repository.findQuestion(session.tipoProva, input.questao_numero);
    if (!question) {
      throw new SimulatorValidationError("Questão não encontrada", 404);
    }
    const isCorrect = isCorrectIncremental(question, input.resposta);
    await this.repository.saveAnswer(
      userId,
      session.id,
      question,
      input.resposta,
      input.tipo_resposta,
      isCorrect,
      ip,
    );
    return {
      success: true as const,
      esta_correta: isCorrect,
      tipo_resposta: input.tipo_resposta,
      questao_numero: input.questao_numero,
      message: "Resposta salva com sucesso",
    };
  }

  async finish(userId: number, rawInput: unknown) {
    const parsed = SimulatorFinishInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new SimulatorValidationError("Dados incompletos", 400);
    }
    const input = parsed.data;
    const session = await this.repository.findSession(userId, input.sessao_id);
    if (!session || session.status !== "ativo") {
      throw new SimulatorValidationError("Sessão não encontrada", 404);
    }
    const questions = await this.repository.listQuestions(session.tipoProva, null);
    const byNumber = new Map(questions.map((question) => [String(question.numero), question]));
    const answers: SimulatorAnswerToSave[] = [];
    let correct = 0;

    for (const [rawNumber, answer] of Object.entries(input.respostas)) {
      const question = byNumber.get(rawNumber);
      if (!question) {
        continue;
      }
      const expected = questionExpectedAnswer(question);
      const isCorrect = phpLower(phpTrim(answer)) === phpLower(phpTrim(expected));
      if (isCorrect) {
        correct += 1;
      }
      answers.push({ question, answer, isCorrect });
    }

    const answered = Object.keys(input.respostas).length;
    const score = answered > 0 ? (correct / answered) * 100 : 0;
    const elapsed = await this.repository.finalizeSession(userId, session.id, answers, {
      score,
      correct,
      answered,
    });
    if (elapsed === null) {
      throw new SimulatorValidationError("Sessão não encontrada", 404);
    }
    return {
      sucesso: true as const,
      pontuacao: score,
      acertos: correct,
      questoes_respondidas: answered,
      tempo_gasto: elapsed,
    };
  }

  private parseSessionId(rawSessionId: unknown): number {
    if (typeof rawSessionId !== "string" || !/^[1-9]\d*$/.test(rawSessionId)) {
      throw new SimulatorValidationError("Sessão inválida.");
    }
    const sessionId = Number(rawSessionId);
    if (!Number.isSafeInteger(sessionId)) {
      throw new SimulatorValidationError("Sessão inválida.");
    }
    return sessionId;
  }
}
