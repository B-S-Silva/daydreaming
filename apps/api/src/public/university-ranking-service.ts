import { UniversityListResponseSchema, type University } from "@daydreaming/shared";
import { universityRankingData } from "./university-ranking-data.js";

export type UniversityRankingSortBy = "rank" | "name" | "score";
export type UniversityRankingOrder = "asc" | "desc";

export interface UniversityRankingQuery {
  country?: string;
  region?: string;
  search?: string;
  sortBy?: UniversityRankingSortBy;
  orderBy?: UniversityRankingOrder;
  page?: number;
  pageSize?: number;
}

export interface UniversityRankingFilters {
  countries: Array<{ code: string; name: string }>;
  regions: string[];
}

export function listUniversityRanking(query: UniversityRankingQuery = {}) {
  const page = query.page ?? 1;
  const pageSize = query.pageSize ?? 10;
  const sortBy = query.sortBy ?? "rank";
  const orderBy = query.orderBy ?? "asc";
  const search = query.search?.trim().toLocaleLowerCase();
  const direction = orderBy === "asc" ? 1 : -1;

  const items = universityRankingData
    .filter((university) => {
      if (query.country && university.countryCode !== query.country) return false;
      if (query.region && university.region !== query.region) return false;
      if (search && !university.name.toLocaleLowerCase().includes(search)) return false;
      return true;
    })
    .sort((left, right) => {
      const comparison =
        sortBy === "name"
          ? left.name.toLocaleLowerCase().localeCompare(right.name.toLocaleLowerCase())
          : left[sortBy] - right[sortBy];
      return comparison === 0 ? left.rank - right.rank : comparison * direction;
    });

  const start = (page - 1) * pageSize;
  return UniversityListResponseSchema.parse({
    items: items.slice(start, start + pageSize),
    page,
    pageSize,
    total: items.length,
  });
}

export function findUniversityById(id: string): University | undefined {
  return universityRankingData.find((university) => university.id === id);
}

export function getUniversityRankingFilters(): UniversityRankingFilters {
  const countries = new Map<string, string>();
  const regions = new Set<string>();
  for (const university of universityRankingData) {
    countries.set(university.countryCode, university.country);
    regions.add(university.region);
  }
  return {
    countries: [...countries].map(([code, name]) => ({ code, name })),
    regions: [...regions].sort(),
  };
}

export function universityRankingRecordCount(): number {
  return universityRankingData.length;
}

export type UniversityRankingResponse = ReturnType<typeof UniversityListResponseSchema.parse>;
