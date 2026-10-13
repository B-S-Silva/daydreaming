// @vitest-environment node
import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { InternationalExamCatalogSchema } from "@daydreaming/shared";
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
});
