import { describe, expect, it } from "vitest";
import { HealthResponseSchema } from "./index.js";

describe("HealthResponseSchema", () => {
  it("accepts the healthy API response", () => {
    expect(HealthResponseSchema.parse({ status: "ok" })).toEqual({ status: "ok" });
  });

  it("rejects an unknown status", () => {
    expect(HealthResponseSchema.safeParse({ status: "down" }).success).toBe(false);
  });
});
