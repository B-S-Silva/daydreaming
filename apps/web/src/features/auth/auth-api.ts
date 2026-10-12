import {
  AuthErrorResponseSchema,
  AuthSuccessResponseSchema,
  CurrentUserResponseSchema,
} from "@daydreaming/shared";
import type {
  AuthSuccessResponse,
  CurrentUserResponse,
  LoginInput,
  RegistrationInput,
} from "@daydreaming/shared";

export class AuthApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "AuthApiError";
  }
}

export async function getCurrentUser(): Promise<CurrentUserResponse> {
  const response = await fetch("/api/auth/me", { credentials: "same-origin" });
  if (!response.ok) {
    throw new AuthApiError("Não foi possível verificar a sessão.");
  }
  return CurrentUserResponseSchema.parse(await response.json());
}

async function submitAuthRequest(
  path: string,
  input: LoginInput | RegistrationInput | undefined,
): Promise<AuthSuccessResponse> {
  const currentUser = await getCurrentUser();
  const response = await fetch(path, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(input ?? {}),
  });

  if (!response.ok) {
    const parsedError = AuthErrorResponseSchema.safeParse(await response.json());
    throw new AuthApiError(
      parsedError.success ? parsedError.data.message : "Não foi possível concluir a solicitação.",
    );
  }

  return AuthSuccessResponseSchema.parse(await response.json());
}

export function login(input: LoginInput): Promise<AuthSuccessResponse> {
  return submitAuthRequest("/api/auth/login", input);
}

export function register(input: RegistrationInput): Promise<AuthSuccessResponse> {
  return submitAuthRequest("/api/auth/register", input);
}

export async function logout(csrfToken: string): Promise<AuthSuccessResponse> {
  const response = await fetch("/api/auth/logout", {
    method: "POST",
    credentials: "same-origin",
    headers: { "x-csrf-token": csrfToken },
  });

  if (!response.ok) {
    const parsedError = AuthErrorResponseSchema.safeParse(await response.json());
    throw new AuthApiError(
      parsedError.success ? parsedError.data.message : "Não foi possível encerrar a sessão.",
    );
  }

  return AuthSuccessResponseSchema.parse(await response.json());
}
