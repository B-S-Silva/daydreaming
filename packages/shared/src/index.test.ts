import { describe, expect, it } from "vitest";
import {
  HealthResponseSchema,
  LoginInputSchema,
  PasswordRecoveryInputSchema,
  RegistrationInputSchema,
} from "./index.js";

describe("HealthResponseSchema", () => {
  it("accepts the healthy API response", () => {
    expect(HealthResponseSchema.parse({ status: "ok" })).toEqual({ status: "ok" });
  });

  it("rejects an unknown status", () => {
    expect(HealthResponseSchema.safeParse({ status: "down" }).success).toBe(false);
  });
});

describe("PHP-compatible authentication form contracts", () => {
  it("trims credentials and treats the PHP empty string value '0' as empty", () => {
    expect(LoginInputSchema.safeParse({ username: " 0 ", password: "senha123" }).success).toBe(
      false,
    );
    expect(LoginInputSchema.parse({ username: " aluno ", password: " senha123 " })).toEqual({
      username: "aluno",
      password: "senha123",
    });
  });

  it("preserves the PHP registration validation messages and byte limits", () => {
    const emptyResult = RegistrationInputSchema.safeParse({
      name: " ",
      username: "0",
      email: "",
      password: "0",
      confirmPassword: "0",
    });
    expect(emptyResult.success).toBe(false);
    if (!emptyResult.success) {
      expect(emptyResult.error.issues.map(({ message }) => message)).toEqual([
        "O nome é obrigatório.",
        "O nome de usuário é obrigatório.",
        "O e-mail é obrigatório.",
        "A senha é obrigatória.",
      ]);
    }

    expect(
      RegistrationInputSchema.safeParse({
        name: "Nome",
        username: "éé",
        email: "aluno@example.com",
        password: "ééé",
        confirmPassword: "ééé",
      }).success,
    ).toBe(true);
  });

  it("validates email recovery without claiming a reset was sent", () => {
    expect(PasswordRecoveryInputSchema.safeParse({ email: "" }).success).toBe(false);
    expect(PasswordRecoveryInputSchema.parse({ email: " aluno@example.com " })).toEqual({
      email: "aluno@example.com",
    });
  });
});
