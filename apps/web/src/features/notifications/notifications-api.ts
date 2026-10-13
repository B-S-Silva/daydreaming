import {
  AuthErrorResponseSchema,
  NotificationListResponseSchema,
  NotificationMutationResponseSchema,
} from "@daydreaming/shared";

export class NotificationsApiError extends Error {
  constructor(
    message: string,
    readonly statusCode: number,
  ) {
    super(message);
    this.name = "NotificationsApiError";
  }
}

async function checkedJson(response: Response): Promise<unknown> {
  const body: unknown = await response.json();
  if (!response.ok) {
    const error = AuthErrorResponseSchema.safeParse(body);
    throw new NotificationsApiError(
      error.success ? error.data.message : "Não foi possível carregar as notificações.",
      response.status,
    );
  }
  return body;
}

export async function getNotifications(scope: "unread" | "all", limit: number) {
  const query = new URLSearchParams({ scope, limit: String(limit) });
  const response = await fetch(`/api/notifications?${query}`, { credentials: "same-origin" });
  return NotificationListResponseSchema.parse(await checkedJson(response));
}

export async function markNotificationRead(id: number, csrfToken: string) {
  const response = await fetch(`/api/notifications/${id}/read`, {
    method: "POST",
    credentials: "same-origin",
    headers: { "x-csrf-token": csrfToken },
  });
  return NotificationMutationResponseSchema.parse(await checkedJson(response));
}

export async function markAllNotificationsRead(csrfToken: string) {
  const response = await fetch("/api/notifications/read-all", {
    method: "POST",
    credentials: "same-origin",
    headers: { "x-csrf-token": csrfToken },
  });
  return NotificationMutationResponseSchema.parse(await checkedJson(response));
}
