import { afterEach, describe, expect, it } from "vitest";
import { createApp } from "../src/app.js";

describe("GET /health", () => {
  const app = createApp();

  afterEach(async () => {
    await app.close();
  });

  it("returns the shared health response contract", async () => {
    const response = await app.inject({ method: "GET", url: "/health" });

    expect(response.statusCode).toBe(200);
    expect(response.json()).toEqual({ status: "ok" });
  });
});
