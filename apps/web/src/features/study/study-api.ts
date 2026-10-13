import {
  AuthErrorResponseSchema,
  CountryExplorationResponseSchema,
  SaveGpaInputSchema,
  SaveGpaResponseSchema,
  StudentProfileResponseSchema,
  StudyMutationResponseSchema,
  UserBadgesResponseSchema,
} from "@daydreaming/shared";
import type { SaveGpaInput, UpdateStudentProfileInput } from "@daydreaming/shared";
import { getCurrentUser } from "../auth/auth-api.js";

export class StudyApiError extends Error {
  constructor(
    message: string,
    readonly statusCode?: number,
  ) {
    super(message);
    this.name = "StudyApiError";
  }
}

async function requestError(response: Response): Promise<StudyApiError> {
  const error = AuthErrorResponseSchema.safeParse(await response.json());
  return new StudyApiError(
    error.success ? error.data.message : "Não foi possível concluir a solicitação.",
    response.status,
  );
}

export async function getStudentProfile() {
  const response = await fetch("/api/profile/me", { credentials: "same-origin" });
  if (!response.ok) {
    throw await requestError(response);
  }
  return StudentProfileResponseSchema.parse(await response.json());
}

export async function getStudentBadges() {
  const response = await fetch("/api/profile/badges", { credentials: "same-origin" });
  if (!response.ok) {
    throw await requestError(response);
  }
  return UserBadgesResponseSchema.parse(await response.json());
}

export async function updateStudentProfile(input: UpdateStudentProfileInput) {
  const currentUser = await getCurrentUser();
  const response = await fetch("/api/profile/me", {
    method: "PUT",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(input),
  });
  if (!response.ok) {
    throw await requestError(response);
  }
  return StudyMutationResponseSchema.parse(await response.json());
}

export async function saveGpa(input: SaveGpaInput) {
  const parsed = SaveGpaInputSchema.parse(input);
  const currentUser = await getCurrentUser();
  const response = await fetch("/api/study/gpa", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(parsed),
  });
  if (!response.ok) {
    throw await requestError(response);
  }
  return SaveGpaResponseSchema.parse(await response.json());
}

export async function recordCountryExploration(countryId: string) {
  const currentUser = await getCurrentUser();
  const response = await fetch(`/api/profile/explored-countries/${encodeURIComponent(countryId)}`, {
    method: "POST",
    credentials: "same-origin",
    headers: { "x-csrf-token": currentUser.csrfToken },
  });
  if (!response.ok) {
    throw await requestError(response);
  }
  return CountryExplorationResponseSchema.parse(await response.json());
}
