import bcrypt from "bcryptjs";
import type { LoginInput, RegistrationInput } from "@daydreaming/shared";
import type { AuthRepository, AuthUser } from "./auth-repository.js";

export type LoginFailure = "invalid-credentials" | "banned";

export type LoginResult =
  | { success: true; user: Omit<AuthUser, "passwordHash" | "active"> }
  | { success: false; reason: LoginFailure };

export type RegistrationResult =
  { success: true; message: string } | { success: false; field: "username" | "email" };

function publicUser(user: AuthUser): Omit<AuthUser, "passwordHash" | "active"> {
  return {
    id: user.id,
    name: user.name,
    username: user.username,
    isAdmin: user.isAdmin,
  };
}

export class AuthService {
  constructor(private readonly repository: AuthRepository) {}

  async login(input: LoginInput, ipAddress: string): Promise<LoginResult> {
    const user = await this.repository.findUserByUsername(input.username);
    if (!user) {
      return { success: false, reason: "invalid-credentials" };
    }

    const passwordMatches = await bcrypt.compare(input.password, user.passwordHash);
    if (!passwordMatches) {
      await this.repository.recordLoginAttempt(user.id, ipAddress, false);
      return { success: false, reason: "invalid-credentials" };
    }

    if (!user.active) {
      await this.repository.recordLoginAttempt(user.id, ipAddress, false);
      return { success: false, reason: "banned" };
    }

    await this.repository.recordLoginAttempt(user.id, ipAddress, true);
    await this.repository.updateLastAccess(user.id);
    return { success: true, user: publicUser(user) };
  }

  async register(input: RegistrationInput): Promise<RegistrationResult> {
    const duplicate = await this.repository.findDuplicateField(input.username, input.email);
    if (duplicate) {
      return { success: false, field: duplicate };
    }

    const passwordHash = await bcrypt.hash(input.password, 10);
    try {
      await this.repository.createUser({
        name: input.name,
        username: input.username,
        email: input.email,
        passwordHash,
      });
    } catch (error) {
      if (
        typeof error === "object" &&
        error !== null &&
        "code" in error &&
        error.code === "ER_DUP_ENTRY"
      ) {
        const concurrentDuplicate = await this.repository.findDuplicateField(
          input.username,
          input.email,
        );
        if (concurrentDuplicate) {
          return { success: false, field: concurrentDuplicate };
        }
      }
      throw error;
    }

    return {
      success: true,
      message: "Cadastro realizado com sucesso! Faça login para continuar.",
    };
  }
}
