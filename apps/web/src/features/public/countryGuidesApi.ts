import { CountryGuideListResponseSchema, CountryGuideSchema } from "@daydreaming/shared";

export class CountryGuidesApiError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "CountryGuidesApiError";
  }
}

async function checkedJson(response: Response): Promise<unknown> {
  const body: unknown = await response.json();
  if (!response.ok) {
    if (
      typeof body === "object" &&
      body !== null &&
      "message" in body &&
      typeof body.message === "string"
    ) {
      throw new CountryGuidesApiError(body.message);
    }
    throw new CountryGuidesApiError("Não foi possível carregar os guias de países.");
  }
  return body;
}

export async function getCountryGuides() {
  const response = await fetch("/api/public/country-guides");
  return CountryGuideListResponseSchema.parse(await checkedJson(response));
}

export async function getCountryGuide(id: string) {
  const response = await fetch(`/api/public/country-guides/${encodeURIComponent(id)}`);
  return CountryGuideSchema.parse(await checkedJson(response));
}
