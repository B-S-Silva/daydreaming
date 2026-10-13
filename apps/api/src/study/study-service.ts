import {
  CountryExplorationResponseSchema,
  SaveGpaInputSchema,
  StudentProfileSchema,
  UpdateStudentProfileInputSchema,
} from "@daydreaming/shared";
import type { SaveGpaInput, StudentProfile } from "@daydreaming/shared";
import { findCountryGuide } from "../public/country-guides.js";
import type { StudyRepository } from "./study-repository.js";

export class StudyValidationError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "StudyValidationError";
  }
}

export class StudyService {
  constructor(private readonly repository: StudyRepository) {}

  async getProfile(userId: number): Promise<StudentProfile> {
    return StudentProfileSchema.parse(await this.repository.findProfile(userId));
  }

  async updateProfile(userId: number, rawInput: unknown): Promise<string> {
    const parsed = UpdateStudentProfileInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new StudyValidationError("Confira os dados do perfil e tente novamente.");
    }
    await this.repository.saveProfile(userId, parsed.data);
    return "Perfil atualizado com sucesso!";
  }

  async saveGpa(userId: number, rawInput: unknown): Promise<string> {
    const parsed = SaveGpaInputSchema.safeParse(rawInput);
    if (!parsed.success) {
      throw new StudyValidationError("Informe um GPA entre 0 e 4 e notas entre 0 e 10.");
    }
    const input: SaveGpaInput = {
      ...parsed.data,
      gpa: Math.round((parsed.data.gpa + Number.EPSILON) * 100) / 100,
    };
    await this.repository.saveGpa(userId, input);
    return "GPA salvo no histórico com sucesso!";
  }

  async trackCountry(userId: number, countryId: string): Promise<{ explored: boolean }> {
    const country = findCountryGuide(countryId);
    if (!country) {
      throw new StudyValidationError("País inválido.");
    }
    return CountryExplorationResponseSchema.parse({
      explored: await this.repository.recordCountryExploration(userId, country.id, country.name),
    });
  }
}
