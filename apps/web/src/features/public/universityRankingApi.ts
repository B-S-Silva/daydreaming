import { UniversityListResponseSchema, UniversitySchema } from "@daydreaming/shared";
import { z } from "zod";

const UniversityRankingFiltersSchema = z.object({
  countries: z.array(z.object({ code: z.string(), name: z.string() })),
  regions: z.array(z.string()),
});

export interface UniversityRankingQuery {
  country?: string;
  region?: string;
  search?: string;
  sortBy?: "rank" | "name" | "score";
  orderBy?: "asc" | "desc";
  page?: number;
  pageSize?: number;
}

export type UniversityRankingFilters = z.infer<typeof UniversityRankingFiltersSchema>;
export type UniversityListResponse = ReturnType<typeof UniversityListResponseSchema.parse>;

async function readJson(response: Response) {
  const body: unknown = await response.json();
  if (!response.ok) {
    const message =
      typeof body === "object" &&
      body !== null &&
      "message" in body &&
      typeof body.message === "string"
        ? body.message
        : `Não foi possível carregar o ranking (${response.status}).`;
    throw new Error(message);
  }
  return body;
}

export async function getUniversityRanking(
  query: UniversityRankingQuery,
): Promise<UniversityListResponse> {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== "") params.set(key, String(value));
  }
  const suffix = params.size > 0 ? `?${params.toString()}` : "";
  return UniversityListResponseSchema.parse(
    await readJson(await fetch(`/api/public/universities${suffix}`)),
  );
}

export async function getUniversityRankingFilters(): Promise<UniversityRankingFilters> {
  return UniversityRankingFiltersSchema.parse(
    await readJson(await fetch("/api/public/universities/filters")),
  );
}

export async function getUniversityById(id: string) {
  return UniversitySchema.parse(
    await readJson(await fetch(`/api/public/universities/${encodeURIComponent(id)}`)),
  );
}
