// @vitest-environment node
import { afterEach, beforeEach, describe, expect, it } from "vitest";
import {
  CountryGuideListResponseSchema,
  CountryGuideSchema,
  InternationalExamCatalogSchema,
} from "@daydreaming/shared";
import { createApp } from "../src/app.js";

describe("public API", () => {
  let app = createApp();

  beforeEach(() => {
    app = createApp();
  });

  afterEach(async () => {
    await app.close();
  });

  it("returns the shared health response contract", async () => {
    const response = await app.inject({ method: "GET", url: "/health" });

    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual({ status: "ok" });
  });

  it("returns the shared international exams catalog contract", async () => {
    const response = await app.inject({
      method: "GET",
      url: "/api/public/international-exams",
    });

    expect(response.statusCode).toBe(200);
    const catalog = InternationalExamCatalogSchema.parse(response.json());
    expect(catalog.countries).toHaveLength(14);
    expect(catalog.exams.map(({ id }) => id)).toEqual([
      "toefl",
      "ielts",
      "sat",
      "act",
      "gre",
      "gmat",
    ]);
    expect(catalog.tips).toHaveLength(8);
  });

  it("returns the 28 country guides and a validated guide detail", async () => {
    const response = await app.inject({ method: "GET", url: "/api/public/country-guides" });
    expect(response.statusCode).toBe(200);

    const catalog = CountryGuideListResponseSchema.parse(response.json());
    expect(catalog.items).toHaveLength(28);
    expect(new Set(catalog.items.map(({ id }) => id)).size).toBe(28);

    const detailResponse = await app.inject({
      method: "GET",
      url: "/api/public/country-guides/alemanha",
    });
    expect(detailResponse.statusCode).toBe(200);
    const detail = CountryGuideSchema.parse(detailResponse.json());
    expect(detail.name).toBe("Alemanha");
    expect(detail.sections.length).toBeGreaterThan(0);
  });

  it("returns not found for an unknown country guide", async () => {
    const response = await app.inject({
      method: "GET",
      url: "/api/public/country-guides/not-a-country",
    });

    expect(response.statusCode).toBe(404);
    expect(response.json()).toEqual({ message: "Guia de país não encontrado." });
  });
});
