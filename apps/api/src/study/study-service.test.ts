import { describe, expect, it, vi } from "vitest";
import type { StudyRepository } from "./study-repository.js";
import { StudyService, StudyValidationError } from "./study-service.js";

function createRepository(): StudyRepository {
  return {
    findProfile: vi.fn(async () => ({
      school: null,
      grade: null,
      location: null,
      gpa: null,
      languages: [],
      exams: [],
      interests: null,
      bio: null,
      countryTrackingEnabled: false,
    })),
    saveProfile: vi.fn(async () => {}),
    saveGpa: vi.fn(async () => {}),
    listBadges: vi.fn(async () => []),
    recordCountryExploration: vi.fn(async () => true),
  };
}

describe("StudyService", () => {
  it("validates and saves GPA history", async () => {
    const repository = createRepository();
    const service = new StudyService(repository);
    const message = await service.saveGpa(7, {
      gpa: 3.456,
      grades: [{ subject: "Matemática", grade: 8.5 }],
    });

    expect(message).toBe("GPA salvo no histórico com sucesso!");
    expect(repository.saveGpa).toHaveBeenCalledWith(7, {
      gpa: 3.46,
      grades: [{ subject: "Matemática", grade: 8.5 }],
    });
  });

  it("rejects invalid GPA input without writing to the repository", async () => {
    const repository = createRepository();
    const service = new StudyService(repository);

    await expect(
      service.saveGpa(7, { gpa: 4.1, grades: [{ subject: "Matemática", grade: 8 }] }),
    ).rejects.toBeInstanceOf(StudyValidationError);
    expect(repository.saveGpa).not.toHaveBeenCalled();
  });

  it("keeps profile access scoped to the repository user id", async () => {
    const repository = createRepository();
    const service = new StudyService(repository);
    await service.getProfile(7);

    expect(repository.findProfile).toHaveBeenCalledWith(7);
  });

  it("accepts only a known guide identifier for country progress", async () => {
    const repository = createRepository();
    const service = new StudyService(repository);
    expect(await service.trackCountry(7, "alemanha")).toEqual({ explored: true });
    expect(repository.recordCountryExploration).toHaveBeenCalledWith(7, "alemanha", "Alemanha");
    await expect(service.trackCountry(7, "unknown")).rejects.toBeInstanceOf(StudyValidationError);
  });

  it("does not claim progress was stored when tracking consent is disabled", async () => {
    const repository = createRepository();
    vi.mocked(repository.recordCountryExploration).mockResolvedValue(false);
    const service = new StudyService(repository);

    expect(await service.trackCountry(7, "alemanha")).toEqual({ explored: false });
  });
});
