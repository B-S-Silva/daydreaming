import { describe, expect, it } from "vitest";
import { calculateGpa, getCareerProfile, scoreInterview } from "./study-model.js";

describe("study tool calculations", () => {
  it("uses the source linear 0–10 to 0–4 GPA conversion", () => {
    expect(
      calculateGpa([
        { subject: "Math", grade: 8 },
        { subject: "History", grade: 6 },
      ]),
    ).toBe(2.8);
  });

  it("rejects an empty or out-of-range grade list", () => {
    expect(calculateGpa([])).toBeNull();
    expect(calculateGpa([{ subject: "Math", grade: 11 }])).toBeNull();
  });

  it("uses the original A–D order to break career quiz ties", () => {
    expect(getCareerProfile({ 0: "B", 1: "A" })?.title).toBe("Gestor/Administrativo");
    expect(getCareerProfile({})).toBeNull();
  });

  it("scores interview answers by length only using the source thresholds", () => {
    expect(scoreInterview(["x".repeat(51), "x".repeat(21), "x".repeat(20)])).toBe(3);
  });
});
