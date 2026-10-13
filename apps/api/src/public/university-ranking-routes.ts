import { UniversitySchema } from "@daydreaming/shared";
import type { FastifyInstance } from "fastify";
import {
  findUniversityById,
  getUniversityRankingFilters,
  listUniversityRanking,
  type UniversityRankingOrder,
  type UniversityRankingSortBy,
} from "./university-ranking-service.js";

type RankingQueryString = {
  country?: string;
  region?: string;
  search?: string;
  sortBy?: string;
  orderBy?: string;
  page?: string;
  pageSize?: string;
};

function parsePositiveInteger(value: string | undefined, fallback: number, field: string): number {
  if (value === undefined) return fallback;
  if (!/^[1-9]\d*$/.test(value)) {
    throw new Error(`Parâmetro ${field} inválido.`);
  }
  const parsed = Number(value);
  if (!Number.isSafeInteger(parsed)) {
    throw new Error(`Parâmetro ${field} inválido.`);
  }
  return parsed;
}

export function registerUniversityRankingRoutes(app: FastifyInstance): void {
  app.get("/api/public/universities/filters", async (_request, reply) => {
    return reply.code(200).send(getUniversityRankingFilters());
  });

  app.get<{ Querystring: RankingQueryString }>(
    "/api/public/universities",
    async (request, reply) => {
      const { country, region, search, sortBy, orderBy } = request.query;
      if (sortBy && !["rank", "name", "score"].includes(sortBy)) {
        return reply.code(400).send({ message: "Parâmetro sortBy inválido." });
      }
      if (orderBy && !["asc", "desc"].includes(orderBy)) {
        return reply.code(400).send({ message: "Parâmetro orderBy inválido." });
      }

      try {
        const page = parsePositiveInteger(request.query.page, 1, "page");
        const pageSize = parsePositiveInteger(request.query.pageSize, 10, "pageSize");
        if (pageSize > 100) {
          return reply.code(400).send({ message: "Parâmetro pageSize não pode exceder 100." });
        }
        const parsedSortBy = sortBy as UniversityRankingSortBy | undefined;
        const parsedOrderBy = orderBy as UniversityRankingOrder | undefined;
        const response = listUniversityRanking({
          ...(country === undefined ? {} : { country }),
          ...(region === undefined ? {} : { region }),
          ...(search === undefined ? {} : { search }),
          ...(parsedSortBy === undefined ? {} : { sortBy: parsedSortBy }),
          ...(parsedOrderBy === undefined ? {} : { orderBy: parsedOrderBy }),
          page,
          pageSize,
        });
        return reply.code(200).send(response);
      } catch (error) {
        return reply
          .code(400)
          .send({ message: error instanceof Error ? error.message : "Parâmetros inválidos." });
      }
    },
  );

  app.get<{ Params: { id: string } }>("/api/public/universities/:id", async (request, reply) => {
    const university = findUniversityById(request.params.id);
    if (!university) {
      return reply.code(404).send({ message: "Universidade não encontrada." });
    }
    return reply.code(200).send(UniversitySchema.parse(university));
  });
}
