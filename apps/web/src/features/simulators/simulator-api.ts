import {
  AuthErrorResponseSchema,
  SimulatorAnswerResponseSchema,
  SimulatorDashboardResponseSchema,
  SimulatorErrorResponseSchema,
  SimulatorFinishResponseSchema,
  SimulatorSessionResponseSchema,
  SimulatorStartResponseSchema,
} from "@daydreaming/shared";
import { getCurrentUser } from "../auth/auth-api.js";

export class SimulatorApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "SimulatorApiError";
  }
}

async function checkedJson(response: Response): Promise<unknown> {
  if (!response.ok) {
    const payload: unknown = await response.json();
    const simulatorError = SimulatorErrorResponseSchema.safeParse(payload);
    const authError = AuthErrorResponseSchema.safeParse(payload);
    const legacyError =
      typeof payload === "object" && payload !== null && "erro" in payload
        ? (payload as { erro: unknown }).erro
        : undefined;
    throw new SimulatorApiError(
      simulatorError.success
        ? simulatorError.data.error
        : authError.success
          ? authError.data.message
          : typeof legacyError === "string"
            ? legacyError
            : "Não foi possível concluir a solicitação.",
    );
  }
  return response.json() as Promise<unknown>;
}

export async function getSimulatorDashboard() {
  const response = await fetch("/api/simulator/dashboard", { credentials: "same-origin" });
  return SimulatorDashboardResponseSchema.parse(await checkedJson(response));
}

export async function startSimulator(tipo_prova: string) {
  const currentUser = await getCurrentUser();
  const response = await fetch("/api/simulator/sessions", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify({ tipo_prova }),
  });
  return SimulatorStartResponseSchema.parse(await checkedJson(response));
}

export async function getSimulatorSession(sessionId: string) {
  const response = await fetch(`/api/simulator/sessions/${encodeURIComponent(sessionId)}`, {
    credentials: "same-origin",
  });
  return SimulatorSessionResponseSchema.parse(await checkedJson(response));
}

export async function saveSimulatorAnswer(input: {
  sessao_id: number;
  questao_numero: number;
  resposta: string;
  tipo_resposta: "multipla_escolha" | "dissertativa";
}) {
  const currentUser = await getCurrentUser();
  const response = await fetch("/api/simulator/answers", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(input),
  });
  return SimulatorAnswerResponseSchema.parse(await checkedJson(response));
}

export async function finishSimulator(input: {
  sessao_id: number;
  finalizar: "1";
  respostas: Record<string, string>;
}) {
  const currentUser = await getCurrentUser();
  const response = await fetch("/api/simulator/finish", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(input),
  });
  return SimulatorFinishResponseSchema.parse(await checkedJson(response));
}
