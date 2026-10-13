import {
  AuthErrorResponseSchema,
  ForumAdminDashboardResponseSchema,
  ForumCategoryListResponseSchema,
  ForumMutationResponseSchema,
  ForumTopicDetailResponseSchema,
  ForumTopicListResponseSchema,
} from "@daydreaming/shared";
import type { ForumAdminDashboardResponse } from "@daydreaming/shared";
import { getCurrentUser } from "../auth/auth-api.js";

export class ForumApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "ForumApiError";
  }
}

async function checkedJson(response: Response): Promise<unknown> {
  if (!response.ok) {
    const parsedError = AuthErrorResponseSchema.safeParse(await response.json());
    throw new ForumApiError(
      parsedError.success ? parsedError.data.message : "Não foi possível concluir a solicitação.",
    );
  }
  return response.json() as Promise<unknown>;
}

export async function getForumCategories() {
  const response = await fetch("/api/forum/categories", { credentials: "same-origin" });
  return ForumCategoryListResponseSchema.parse(await checkedJson(response));
}

export async function getForumTopics(params: { categoria?: number; busca?: string } = {}) {
  const query = new URLSearchParams();
  if (params.categoria !== undefined) {
    query.set("categoria", String(params.categoria));
  }
  if (params.busca) {
    query.set("busca", params.busca);
  }
  const suffix = query.size ? `?${query.toString()}` : "";
  const response = await fetch(`/api/forum/topics${suffix}`, { credentials: "same-origin" });
  return ForumTopicListResponseSchema.parse(await checkedJson(response));
}

export async function getForumTopic(id: string) {
  const response = await fetch(`/api/forum/topics/${encodeURIComponent(id)}`, {
    credentials: "same-origin",
  });
  return ForumTopicDetailResponseSchema.parse(await checkedJson(response));
}

export async function getForumAdminDashboard(
  filter = "todos",
): Promise<ForumAdminDashboardResponse> {
  const response = await fetch(
    `/api/admin/forum/dashboard?${new URLSearchParams({ filtro: filter })}`,
    { credentials: "same-origin" },
  );
  return ForumAdminDashboardResponseSchema.parse(await checkedJson(response));
}

export async function submitForumMutation(
  path: string,
  input: unknown,
): Promise<{ success: true; message: string }> {
  const currentUser = await getCurrentUser();
  const response = await fetch(path, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "content-type": "application/json",
      "x-csrf-token": currentUser.csrfToken,
    },
    body: JSON.stringify(input),
  });
  return ForumMutationResponseSchema.parse(await checkedJson(response));
}
