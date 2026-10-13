import { and, count, desc, eq, gt, sql } from "drizzle-orm";
import type { MySql2Database } from "drizzle-orm/mysql2";
import type {
  Badge,
  SaveGpaInput,
  StudentProfile,
  UpdateStudentProfileInput,
} from "@daydreaming/shared";
import * as schema from "../db/schema.js";
import { badgeFor, type BadgeCode } from "./badges.js";

export interface StudyRepository {
  findProfile(userId: number): Promise<StudentProfile>;
  saveProfile(userId: number, input: UpdateStudentProfileInput): Promise<void>;
  saveGpa(userId: number, input: SaveGpaInput): Promise<void>;
  listBadges(userId: number): Promise<Badge[]>;
  recordCountryExploration(
    userId: number,
    countryCode: string,
    countryName: string,
  ): Promise<boolean>;
}

function parseList(value: string | null): string[] {
  if (value === null) {
    return [];
  }
  const parsed: unknown = JSON.parse(value);
  if (!Array.isArray(parsed) || !parsed.every((item): item is string => typeof item === "string")) {
    throw new Error("Invalid list data in student profile.");
  }
  return parsed;
}

export class MySqlStudyRepository implements StudyRepository {
  constructor(private readonly db: MySql2Database<typeof schema>) {}

  async findProfile(userId: number): Promise<StudentProfile> {
    const [row] = await this.db
      .select()
      .from(schema.perfilUsuario)
      .where(eq(schema.perfilUsuario.usuarioId, userId))
      .limit(1);
    if (!row) {
      return {
        school: null,
        grade: null,
        location: null,
        gpa: null,
        languages: [],
        exams: [],
        interests: null,
        bio: null,
        countryTrackingEnabled: false,
      };
    }
    return {
      school: row.escola,
      grade: row.serie,
      location: row.localizacao,
      gpa: row.gpa === null ? null : Number(row.gpa),
      languages: parseList(row.idiomas),
      exams: parseList(row.exames),
      interests: row.interesses,
      bio: row.biografia,
      countryTrackingEnabled: row.rastreamentoPaises,
    };
  }

  async saveProfile(userId: number, input: UpdateStudentProfileInput): Promise<void> {
    const values = {
      usuarioId: userId,
      escola: input.school,
      serie: input.grade,
      localizacao: input.location,
      gpa: input.gpa === null ? null : input.gpa.toFixed(2),
      idiomas: JSON.stringify(input.languages),
      exames: JSON.stringify(input.exams),
      interesses: input.interests,
      biografia: input.bio,
      rastreamentoPaises: input.countryTrackingEnabled,
      dataAtualizacao: new Date(),
    };
    await this.db
      .insert(schema.perfilUsuario)
      .values(values)
      .onDuplicateKeyUpdate({
        set: {
          escola: values.escola,
          serie: values.serie,
          localizacao: values.localizacao,
          gpa: values.gpa,
          idiomas: values.idiomas,
          exames: values.exames,
          interesses: values.interesses,
          biografia: values.biografia,
          rastreamentoPaises: values.rastreamentoPaises,
          dataAtualizacao: values.dataAtualizacao,
        },
      });
  }

  async saveGpa(userId: number, input: SaveGpaInput): Promise<void> {
    const gpa = Math.round((input.gpa + Number.EPSILON) * 100) / 100;
    const now = new Date();
    await this.db.transaction(async (transaction) => {
      await transaction.insert(schema.usuarioGpa).values({
        usuarioId: userId,
        gpa: gpa.toFixed(2),
        notasUtilizadas: JSON.stringify(input.grades),
        dataCalculo: now,
      });
      await transaction
        .insert(schema.perfilUsuario)
        .values({ usuarioId: userId, gpa: gpa.toFixed(2), dataAtualizacao: now })
        .onDuplicateKeyUpdate({
          set: { gpa: gpa.toFixed(2), dataAtualizacao: now },
        });
      await transaction.insert(schema.historicoAtividades).values({
        usuarioId: userId,
        tipo: "gpa",
        descricao: "Calculou o GPA",
        detalhes: JSON.stringify({ gpa }),
      });
    });
  }

  async listBadges(userId: number): Promise<Badge[]> {
    const [bestTest] = await this.db
      .select({
        percentage: sql<
          number | null
        >`MAX(100.0 * ${schema.resultadosTestes.acertos} / NULLIF(${schema.resultadosTestes.totalQuestoes}, 0))`,
      })
      .from(schema.resultadosTestes)
      .where(
        and(
          eq(schema.resultadosTestes.usuarioId, userId),
          gt(schema.resultadosTestes.totalQuestoes, 0),
        ),
      );
    const [topics] = await this.db
      .select({ total: count() })
      .from(schema.forumTopicos)
      .where(eq(schema.forumTopicos.autorId, userId));
    const [replies] = await this.db
      .select({ total: count() })
      .from(schema.forumRespostas)
      .where(eq(schema.forumRespostas.autorId, userId));
    const [bestGpa] = await this.db
      .select({ value: sql<number | null>`MAX(${schema.usuarioGpa.gpa})` })
      .from(schema.usuarioGpa)
      .where(eq(schema.usuarioGpa.usuarioId, userId));
    const [countries] = await this.db
      .select({ total: sql<number>`COUNT(DISTINCT ${schema.paisesVisitados.codigoPais})` })
      .from(schema.paisesVisitados)
      .where(eq(schema.paisesVisitados.usuarioId, userId));

    const participationCount = Number(topics?.total ?? 0) + Number(replies?.total ?? 0);
    const gpaValue = bestGpa === undefined || bestGpa.value === null ? null : Number(bestGpa.value);
    const countryCount = Number(countries?.total ?? 0);
    const achievements: Array<{ code: BadgeCode; context: string }> = [];
    const candidates = [
      {
        code:
          bestTest?.percentage === null || bestTest?.percentage === undefined
            ? null
            : badgeFor("provas", Number(bestTest.percentage)),
        context:
          bestTest?.percentage === null || bestTest?.percentage === undefined
            ? null
            : `Prova com ${Number(bestTest.percentage).toFixed(2)}% de acertos`,
      },
      {
        code: badgeFor("forum", participationCount),
        context: `${participationCount} participações no fórum`,
      },
      {
        code: gpaValue === null ? null : badgeFor("gpa", gpaValue),
        context: gpaValue === null ? null : `GPA de ${gpaValue}`,
      },
      {
        code: badgeFor("paises", countryCount),
        context: `${countryCount} países visitados`,
      },
    ];

    for (const candidate of candidates) {
      if (candidate.code && candidate.context) {
        achievements.push({ code: candidate.code, context: candidate.context });
      }
    }

    await this.db.transaction(async (transaction) => {
      for (const achievement of achievements) {
        const [badge] = await transaction
          .select()
          .from(schema.badges)
          .where(and(eq(schema.badges.codigo, achievement.code), eq(schema.badges.ativa, true)))
          .limit(1);
        if (!badge) {
          continue;
        }
        const [metadata] = await transaction.insert(schema.usuarioBadges).ignore().values({
          usuarioId: userId,
          badgeId: badge.id,
          contexto: achievement.context,
          dataConquista: new Date(),
        });
        if (metadata.affectedRows === 0) {
          continue;
        }
        await transaction.insert(schema.notificacoesUsuario).values({
          usuarioId: userId,
          tipo: "badge_conquistada",
          titulo: "Nova conquista desbloqueada!",
          mensagem: `Parabéns! Você conquistou a badge "${badge.nome}": ${badge.descricao}`,
          link: "/perfil#badges",
        });
      }
    });

    const rows = await this.db
      .select({
        code: schema.badges.codigo,
        name: schema.badges.nome,
        description: schema.badges.descricao,
        icon: schema.badges.icone,
        rarity: schema.badges.raridade,
        acquiredAt: schema.usuarioBadges.dataConquista,
        context: schema.usuarioBadges.contexto,
      })
      .from(schema.usuarioBadges)
      .innerJoin(schema.badges, eq(schema.usuarioBadges.badgeId, schema.badges.id))
      .where(eq(schema.usuarioBadges.usuarioId, userId))
      .orderBy(desc(schema.usuarioBadges.dataConquista));
    return rows.map((row) => {
      const category: Badge["category"] = row.code.startsWith("prova_")
        ? "provas"
        : row.code.startsWith("forum_")
          ? "forum"
          : row.code.startsWith("gpa_")
            ? "gpa"
            : row.code.startsWith("paises_")
              ? "paises"
              : "outros";
      const rarity: Badge["rarity"] =
        row.rarity === "raro" || row.rarity === "epico" || row.rarity === "lendario"
          ? row.rarity
          : "comum";
      return {
        code: row.code,
        name: row.name,
        description: row.description,
        icon: row.icon,
        category,
        rarity,
        acquiredAt: row.acquiredAt.toISOString(),
        context: row.context,
      };
    });
  }

  async recordCountryExploration(
    userId: number,
    countryCode: string,
    countryName: string,
  ): Promise<boolean> {
    return this.db.transaction(async (transaction) => {
      const [profile] = await transaction
        .select({ enabled: schema.perfilUsuario.rastreamentoPaises })
        .from(schema.perfilUsuario)
        .where(eq(schema.perfilUsuario.usuarioId, userId))
        .limit(1);
      if (!profile?.enabled) {
        return false;
      }

      const now = new Date();
      await transaction
        .insert(schema.paisesVisitados)
        .values({
          usuarioId: userId,
          codigoPais: countryCode,
          nomePais: countryName,
          primeiraVisita: now,
          ultimaVisita: now,
        })
        .onDuplicateKeyUpdate({
          set: {
            ultimaVisita: now,
            totalVisitas: sql`${schema.paisesVisitados.totalVisitas} + 1`,
          },
        });
      await transaction.insert(schema.historicoAtividades).values({
        usuarioId: userId,
        tipo: "pais_explorado",
        descricao: `Abriu o guia de ${countryName}`,
        detalhes: JSON.stringify({ countryCode }),
      });
      return true;
    });
  }
}
