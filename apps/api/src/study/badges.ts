export type BadgeCode =
  | "prova_bronze"
  | "prova_prata"
  | "prova_ouro"
  | "prova_rubi"
  | "prova_diamante"
  | "forum_bronze"
  | "forum_prata"
  | "forum_ouro"
  | "forum_rubi"
  | "forum_diamante"
  | "gpa_bronze"
  | "gpa_prata"
  | "gpa_ouro"
  | "gpa_rubi"
  | "gpa_diamante"
  | "paises_bronze"
  | "paises_prata"
  | "paises_ouro"
  | "paises_rubi"
  | "paises_diamante";

const thresholds: Record<
  "provas" | "forum" | "gpa" | "paises",
  ReadonlyArray<{ minimum: number; code: BadgeCode }>
> = {
  provas: [
    { minimum: 20, code: "prova_bronze" },
    { minimum: 40, code: "prova_prata" },
    { minimum: 60, code: "prova_ouro" },
    { minimum: 80, code: "prova_rubi" },
    { minimum: 100, code: "prova_diamante" },
  ],
  forum: [
    { minimum: 1, code: "forum_bronze" },
    { minimum: 3, code: "forum_prata" },
    { minimum: 5, code: "forum_ouro" },
    { minimum: 7, code: "forum_rubi" },
    { minimum: 9, code: "forum_diamante" },
  ],
  gpa: [
    { minimum: 2, code: "gpa_bronze" },
    { minimum: 2.5, code: "gpa_prata" },
    { minimum: 3, code: "gpa_ouro" },
    { minimum: 3.5, code: "gpa_rubi" },
    { minimum: 4, code: "gpa_diamante" },
  ],
  paises: [
    { minimum: 5, code: "paises_bronze" },
    { minimum: 10, code: "paises_prata" },
    { minimum: 15, code: "paises_ouro" },
    { minimum: 20, code: "paises_rubi" },
    { minimum: 28, code: "paises_diamante" },
  ],
};

export function badgeFor(category: keyof typeof thresholds, value: number): BadgeCode | null {
  let awarded: BadgeCode | null = null;
  for (const threshold of thresholds[category]) {
    if (value < threshold.minimum) {
      break;
    }
    awarded = threshold.code;
  }
  return awarded;
}
