import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { Link } from "react-router";
import { PublicHeader } from "../auth/AuthPageLayout.js";
import { getStudentProfile, saveGpa, StudyApiError } from "./study-api.js";
import { careerQuizQuestions } from "./career-quiz-content.js";
import { calculateGpa, getCareerProfile, scoreInterview } from "./study-model.js";
import type { CareerChoice, StudyGrade } from "./study-model.js";

type Tool = "gpa" | "career" | "interview";
interface GradeRow {
  id: number;
  subject: string;
  grade: string;
}

const interviewQuestions = [
  "Por favor, fale um pouco sobre você e sua trajetória acadêmica.",
  "Quais são suas principais qualidades e defeitos?",
  "O que motivou sua escolha por estudar neste país em específico?",
  "Por que você escolheu este curso para sua formação?",
  "Quais razões levaram você a buscar este curso fora do Brasil?",
  "Você pretende voltar para o Brasil após os estudos? Se sim, o que pretende levar de aprendizado para o país?",
  "Quais são seus planos e objetivos para o futuro profissional?",
  "Você já teve experiência morando sozinho? Como foi?",
  "Quais aspectos você mais aprecia na cultura e sociedade deste país?",
  "Existe algo neste país que você considera um desafio ou que não gosta?",
  "Por que acredita que deve ser selecionado para receber a bolsa de estudos?",
  "De que forma pretende contribuir para a comunidade acadêmica local?",
  "Quais objetivos profissionais você espera alcançar após concluir a bolsa?",
  "Como você costuma lidar com desafios e situações multiculturais?",
];

export function StudyToolsPage() {
  const [tool, setTool] = useState<Tool>("gpa");

  return (
    <>
      <PublicHeader />
      <main className="study-page content-width">
        <header className="results-heading">
          <h1>Ferramentas de planejamento acadêmico</h1>
          <p>
            Calcule uma estimativa de GPA, explore interesses profissionais e pratique entrevistas.
          </p>
        </header>
        <nav className="study-tabs" aria-label="Ferramentas de estudo">
          <button aria-pressed={tool === "gpa"} onClick={() => setTool("gpa")} type="button">
            Calculadora GPA
          </button>
          <button aria-pressed={tool === "career"} onClick={() => setTool("career")} type="button">
            Quiz vocacional
          </button>
          <button
            aria-pressed={tool === "interview"}
            onClick={() => setTool("interview")}
            type="button"
          >
            Prática de entrevista
          </button>
        </nav>
        {tool === "gpa" && <GpaCalculator />}
        {tool === "career" && <CareerQuiz />}
        {tool === "interview" && <InterviewPractice />}
      </main>
    </>
  );
}

function GpaCalculator() {
  const queryClient = useQueryClient();
  const profileQuery = useQuery({
    queryKey: ["student-profile"],
    queryFn: getStudentProfile,
  });
  const [rows, setRows] = useState<GradeRow[]>([{ id: 1, subject: "", grade: "" }]);
  const [nextId, setNextId] = useState(2);
  const validGrades = useMemo(() => {
    if (
      rows.length === 0 ||
      rows.some(
        (row) =>
          !row.subject.trim() ||
          row.grade.trim() === "" ||
          !Number.isFinite(Number(row.grade)) ||
          Number(row.grade) < 0 ||
          Number(row.grade) > 10,
      )
    ) {
      return null;
    }
    return rows.map((row): StudyGrade => ({
      subject: row.subject.trim(),
      grade: Number(row.grade),
    }));
  }, [rows]);
  const result = validGrades ? calculateGpa(validGrades) : null;
  const saveMutation = useMutation({
    mutationFn: () => {
      if (!validGrades || result === null) {
        throw new Error("Preencha todas as notas entre 0 e 10.");
      }
      return saveGpa({ gpa: result, grades: validGrades });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["student-profile"] });
    },
  });

  function updateRow(id: number, field: "subject" | "grade", value: string) {
    setRows((current) => current.map((row) => (row.id === id ? { ...row, [field]: value } : row)));
  }

  function addRow() {
    setRows((current) => [...current, { id: nextId, subject: "", grade: "" }]);
    setNextId((current) => current + 1);
  }

  return (
    <section className="study-tool-card">
      <div style={{ display: "flex", alignItems: "center", gap: "16px", marginBottom: "20px" }}>
        <img
          src="/images/calc.png"
          alt="Calculadora de GPA"
          style={{ height: "48px", width: "48px", objectFit: "contain" }}
        />
        <div>
          <h2 style={{ margin: 0 }}>Calculadora de GPA</h2>
          <p style={{ margin: "4px 0 0", color: "var(--slate-600)", fontSize: "0.95rem" }}>
            Adicione suas notas em uma escala de 0 a 10 para obter uma estimativa na escala 0–4.
          </p>
        </div>
      </div>
      <div className="gpa-rows">
        {rows.map((row, index) => (
          <div className="gpa-row" key={row.id}>
            <label>
              <span>Matéria {index + 1}</span>
              <input
                maxLength={100}
                onChange={(event) => updateRow(row.id, "subject", event.target.value)}
                value={row.subject}
              />
            </label>
            <label>
              <span>Nota (0–10)</span>
              <input
                max="10"
                min="0"
                onChange={(event) => updateRow(row.id, "grade", event.target.value)}
                step="0.01"
                type="number"
                value={row.grade}
              />
            </label>
            {rows.length > 1 && (
              <button
                aria-label={`Remover matéria ${index + 1}`}
                onClick={() => setRows((current) => current.filter(({ id }) => id !== row.id))}
                type="button"
              >
                Remover
              </button>
            )}
          </div>
        ))}
      </div>
      <div className="button-row">
        <button className="button button--secondary" onClick={addRow} type="button">
          Adicionar matéria
        </button>
      </div>
      {result !== null && (
        <p className="gpa-result" aria-live="polite">
          GPA estimado: <strong>{result.toFixed(2)} / 4.00</strong>
        </p>
      )}
      <p className="guide-disclaimer">
        Esta conversão linear segue a aproximação do guia anterior e não substitui a equivalência
        oficial exigida por universidades.
      </p>
      {profileQuery.data?.profile.gpa !== null && profileQuery.data?.profile.gpa !== undefined && (
        <p>Último GPA salvo: {profileQuery.data.profile.gpa.toFixed(2)}</p>
      )}
      {profileQuery.error && (
        <p role="alert">
          {profileQuery.error instanceof StudyApiError
            ? profileQuery.error.message
            : "Não foi possível carregar seu perfil."}{" "}
          {profileQuery.error instanceof StudyApiError && profileQuery.error.statusCode === 401 && (
            <Link to="/login">Fazer login</Link>
          )}
        </p>
      )}
      {saveMutation.error && (
        <p role="alert">
          {saveMutation.error instanceof StudyApiError
            ? saveMutation.error.message
            : saveMutation.error.message}
        </p>
      )}
      <button
        className="button button--primary"
        disabled={result === null || saveMutation.isPending}
        onClick={() => saveMutation.mutate()}
        type="button"
      >
        {saveMutation.isPending ? "Salvando..." : "Salvar no meu histórico"}
      </button>
      {saveMutation.isSuccess && <p role="status">{saveMutation.data.message}</p>}
    </section>
  );
}

function CareerQuiz() {
  const [answers, setAnswers] = useState<Partial<Record<number, CareerChoice>>>({});
  const [showResult, setShowResult] = useState(false);
  const result = showResult ? getCareerProfile(answers) : null;

  return (
    <section className="study-tool-card">
      <div style={{ display: "flex", alignItems: "center", gap: "16px", marginBottom: "20px" }}>
        <img
          src="/images/descoberta.png"
          alt="Quiz vocacional"
          style={{ height: "48px", width: "48px", objectFit: "contain" }}
        />
        <div>
          <h2 style={{ margin: 0 }}>Quiz vocacional exploratório</h2>
          <p style={{ margin: "4px 0 0", color: "var(--slate-600)", fontSize: "0.95rem" }}>
            Escolha a opção que mais combina com você. Não é uma avaliação profissional validada.
          </p>
        </div>
      </div>
      <div className="career-questions">
        {careerQuizQuestions.map((question, index) => (
          <fieldset key={question.question}>
            <legend>{question.question}</legend>
            {question.options.map((option) => {
              const value = option.slice(0, 1) as CareerChoice;
              return (
                <label key={value}>
                  <input
                    checked={answers[index] === value}
                    name={`career-${index}`}
                    onChange={() => setAnswers((current) => ({ ...current, [index]: value }))}
                    type="radio"
                    value={value}
                  />
                  {option}
                </label>
              );
            })}
          </fieldset>
        ))}
      </div>
      <button
        className="button button--primary"
        disabled={Object.keys(answers).length === 0}
        onClick={() => setShowResult(true)}
        type="button"
      >
        Ver perfil exploratório
      </button>
      {showResult && !result && <p role="status">Responda ao menos uma pergunta.</p>}
      {result && (
        <article className="career-result" aria-live="polite">
          <h3>{result.title}</h3>
          <p>{result.description}</p>
          <h4>Áreas profissionais para explorar</h4>
          {result.careers.map((career) => (
            <section key={career.title}>
              <h5>{career.title}</h5>
              <p>{career.description}</p>
              <p>
                <strong>Habilidades relacionadas:</strong> {career.skills.join(", ")}.
              </p>
            </section>
          ))}
          <h4>
            {result.country.flag} País relacionado ao perfil: {result.country.name}
          </h4>
          <p>{result.country.reason}</p>
          <Link to={result.country.href}>Explorar guia de {result.country.name}</Link>
        </article>
      )}
    </section>
  );
}

function InterviewPractice() {
  const [answers, setAnswers] = useState<string[]>(() => interviewQuestions.map(() => ""));
  const [showScore, setShowScore] = useState(false);
  const score = scoreInterview(answers);

  return (
    <section className="study-tool-card">
      <div style={{ display: "flex", alignItems: "center", gap: "16px", marginBottom: "20px" }}>
        <img
          src="/images/simulador-entrevista.png"
          alt="Prática de entrevista"
          style={{ height: "48px", width: "48px", objectFit: "contain" }}
        />
        <div>
          <h2 style={{ margin: 0 }}>Prática de entrevista</h2>
          <p style={{ margin: "4px 0 0", color: "var(--slate-600)", fontSize: "0.95rem" }}>
            Escreva rascunhos para as perguntas. O indicador considera apenas o tamanho das
            respostas, não avalia conteúdo, clareza ou qualidade.
          </p>
        </div>
      </div>
      {interviewQuestions.map((question, index) => (
        <label className="interview-answer" key={question}>
          <span>
            {index + 1}. {question}
          </span>
          <textarea
            onChange={(event) =>
              setAnswers((current) =>
                current.map((answer, answerIndex) =>
                  answerIndex === index ? event.target.value : answer,
                ),
              )
            }
            rows={3}
            value={answers[index]}
          />
        </label>
      ))}
      <button className="button button--primary" onClick={() => setShowScore(true)} type="button">
        Ver indicador de preenchimento
      </button>
      {showScore && (
        <div aria-live="polite">
          <p className="gpa-result">
            Indicador de preenchimento: <strong>{score}/28 pontos</strong>
          </p>
          <ul>
            {answers.map((answer, index) => {
              const length = answer.trim().length;
              const feedback =
                length > 50
                  ? "Boa resposta! Mostrou profundidade."
                  : length > 20
                    ? "Resposta razoável."
                    : "Resposta muito curta.";
              return <li key={interviewQuestions[index]}>{feedback}</li>;
            })}
          </ul>
        </div>
      )}
    </section>
  );
}
