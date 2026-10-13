import Fastify from "fastify";
import { afterEach, describe, expect, it, vi } from "vitest";
import {
  findUniversityById,
  getUniversityRankingFilters,
  listUniversityRanking,
  universityRankingRecordCount,
} from "./university-ranking-service.js";
import { registerUniversityRankingRoutes } from "./university-ranking-routes.js";

afterEach(() => vi.restoreAllMocks());

describe("university ranking static service", () => {
  it("preserves all 89 canonical rows and detail metrics", () => {
    expect(universityRankingRecordCount()).toBe(89);
    expect(findUniversityById("1")).toMatchObject({
      name: "Massachusetts Institute of Technology (MIT)",
      rank: 1,
      details: expect.arrayContaining([
        expect.objectContaining({ title: "Pontuação Geral", value: "100.0" }),
      ]),
    });
    expect(findUniversityById("89")).toMatchObject({
      name: "University of Stirling",
      rank: 89,
      details: expect.arrayContaining([
        expect.objectContaining({ title: "Proporção de Alunos por Professor" }),
      ]),
    });
    expect(findUniversityById("90")).toBeUndefined();
  });

  it("filters by country, region and name and sorts in either direction", () => {
    const filtered = listUniversityRanking({
      country: "gb",
      region: "europe",
      search: "cambridge",
    });
    expect(filtered.total).toBe(1);
    expect(filtered.items[0]?.name).toBe("University of Cambridge");

    expect(listUniversityRanking({ sortBy: "rank", orderBy: "desc" }).items[0]?.rank).toBe(89);
    expect(listUniversityRanking({ sortBy: "score", orderBy: "asc" }).items[0]?.score).toBe(61.6);
    const names = listUniversityRanking({ sortBy: "name" }).items.map(({ name }) => name);
    expect(names).toEqual(
      [...names].sort((left, right) => left.toLowerCase().localeCompare(right.toLowerCase())),
    );
  });

  it("paginates the filtered result and derives selector choices from canonical data", () => {
    const firstPage = listUniversityRanking({ page: 1, pageSize: 10 });
    const secondPage = listUniversityRanking({ page: 2, pageSize: 10 });
    expect(firstPage.total).toBe(89);
    expect(firstPage.items).toHaveLength(10);
    expect(secondPage.items).toHaveLength(10);
    expect(secondPage.items[0]?.rank).toBe(11);

    const filters = getUniversityRankingFilters();
    expect(filters.countries).toContainEqual({ code: "us", name: "Estados Unidos" });
    expect(filters.regions).toContain("europe");
  });
});

describe("university ranking Fastify routes", () => {
  it("serves list, filters and detail with the existing shared response shape", async () => {
    const app = Fastify();
    registerUniversityRankingRoutes(app);

    const list = await app.inject({
      method: "GET",
      url: "/api/public/universities?page=2&pageSize=5",
    });
    expect(list.statusCode).toBe(200);
    expect(list.json()).toMatchObject({ page: 2, pageSize: 5, total: 89 });
    expect(list.json().items).toHaveLength(5);

    const filters = await app.inject({ method: "GET", url: "/api/public/universities/filters" });
    expect(filters.statusCode).toBe(200);
    expect(filters.json().regions).toContain("asia");

    const detail = await app.inject({ method: "GET", url: "/api/public/universities/1" });
    expect(detail.statusCode).toBe(200);
    expect(detail.json()).toMatchObject({
      id: "1",
      name: "Massachusetts Institute of Technology (MIT)",
      details: expect.arrayContaining([
        expect.objectContaining({
          title: "Reputação Acadêmica",
          description: "Baseado em pesquisas acadêmicas",
        }),
      ]),
    });

    const missing = await app.inject({ method: "GET", url: "/api/public/universities/999" });
    expect(missing.statusCode).toBe(404);
    await app.close();
  });

  it("rejects unsupported sorting and invalid pagination parameters", async () => {
    const app = Fastify();
    registerUniversityRankingRoutes(app);

    const invalidSort = await app.inject({
      method: "GET",
      url: "/api/public/universities?sortBy=subject",
    });
    expect(invalidSort.statusCode).toBe(400);

    const invalidPage = await app.inject({
      method: "GET",
      url: "/api/public/universities?page=0",
    });
    expect(invalidPage.statusCode).toBe(400);

    const oversizedPage = await app.inject({
      method: "GET",
      url: "/api/public/universities?pageSize=101",
    });
    expect(oversizedPage.statusCode).toBe(400);
    await app.close();
  });
});
